# Rework plan: Exten-gen, Gen-gen, Meta-gen

Drawn up 2026-09-18, from an analysis of Extengen at commit `a1acd77`.

Steps are numbered `stage.step` so that a single step can be referenced as a unit of
work, for example "1.4". Each step states what it produces and when it is done.

## Settled constraints

Three decisions shape everything below.

**What the generator projects share lives in one installable Joomla library.** Not one
copy per extension: `lib_yepr_gen`, under the `Yepr\Gen` namespace, carrying the engine
and the third-party packages it needs. Every extension checks for it on install and
installs it when missing, as the Regular Labs and Akeeba libraries do. The same source
tree also publishes a composer package, which keeps the engine usable outside Joomla -
Drupal, Symfony, anything PHP.

**Exten-gen v1 is deliberately minimal.** Joomla's built-in features — categories, tags,
versioning, workflow, custom fields, full ACL, routing, action logs, finder — are out of
v1. They land after Stage 2, when adding a feature is cheap. Some may need no more than a
toggle; others will need more of the model.

**Joomla 6 is the only target for now**, and targets are pluggable from the start. Later
targets need not be Joomla versions at all.

## The shape

### Repositories

| Repo | Contains | Ships as |
|---|---|---|
| `generator-core` *(new)* | framework-agnostic generation engine, at `Yepr\Gen\Core` | `yepr/generator-core` (composer) **+** `lib_yepr_gen` (Joomla library) |
| `Exten-gen` *(new, from Extengen)* | `com_extengen` — models extensions, generates them | component package |
| `Gen-gen` *(new, stage 2)* | models generators | component package |
| `Meta-gen` *(new, stage 3)* | models the model language, generates forms | component package |
| `plug-gen` *(exists)* | plugin types | adopts the core at 4.1 |

### Layers

```
source model  ──transformation──▶  target structure model  ──emitters──▶  FileCollection ──▶ zip
(ER1: entities,                    (Joomla 6 component:                   (in memory,
 pages, extension)                  files, classes, forms)                  never disk)
```

A target is a structure metamodel plus emitters plus a template set. Nothing above the
emitters knows what a Joomla is.

### Why this order

The three components are mutually dependent: Exten-gen's forms should come from Meta-gen,
its generators from Gen-gen, and both of those are themselves components that Exten-gen
should generate. The cycle breaks by hand-writing one layer and bootstrapping from it.

The hand-written layer is the generator *core* — the runtime that executes a generator.
That is not the same thing as Gen-gen, which is the modelling tool that produces one.

Gen-gen cannot come first, because a generator is a transformation between two
metamodels and neither endpoint currently exists as a nameable thing. The model language
is implicit, spread across 25 XML form files with its real semantics living in the PHP
that reads them. The target has no representation at all: "a Joomla component" exists
only as the output side-effect of seven hand-written PHP classes. Stage 1 makes both
endpoints explicit, which is what makes Stage 2 possible.

Writing generators by hand in Stage 1 that Stage 2 will later regenerate is not waste.
It is the reference implementation Gen-gen must reproduce, and without it Gen-gen has
nothing to be checked against. MPS was bootstrapped the same way.

---

## Stage 0 — the core

Independent of Extengen; can start immediately.

**0.1 Create the repository and skeleton.** `generator-core`, following plug-gen's setup:
`composer.json` (`yepr/generator-core`, PHP >= 8.3 — Joomla 6's minimum — and no runtime
dependencies), PHPUnit, PHPStan, php-cs-fixer, phpcs, `docs/`, GitHub release workflow.
*Done when* `composer test` and `composer analyse` run green on an empty suite.

**0.2 Extract the engine from plug-gen.** Move and generalise into `Yepr\Gen\Core\`:
`FileCollection`, `ZipWriter`, `Renderer`, emitters (`PhpEmitter`, `XmlEmitter`,
`IniEmitter`), `Pipeline`, `GeneratorInterface`, `ProtectedRegionMerger`. Port
`NoJoomlaDependencyTest` — that rule is what keeps the core reusable.
*Done when* the suite passes with zero Joomla or CMS imports anywhere in `src/`.

**0.3 The text layer.** *Decided: Twig, as a hard dependency.* The core defines a
`RendererInterface` so that no generator is coupled to an engine, ships `TwigRenderer` as
the default and `PhpRenderer` for a consumer that wants no engine. Twig sits in the
composer package's `require` and ships inside the Joomla library, resolved by composer at
build time. Exactly one class imports it, and a test enforces that, so the choice stays a
registration rather than a rewrite.

Measured on PHP 8.3.6 with Twig 3.29, rendering an identical Joomla Table class from a
Twig template and from a native-PHP template (both produced byte-identical output):

| | Twig | native PHP |
|---|---|---|
| cold, empty cache, 1 render | 2.16 ms | 0.36 ms |
| warm, new Environment per render | 0.284 ms | — |
| warm, one Environment reused | 0.166 ms | 0.121 ms |
| template held as a string, cached | 0.172 ms | needs `eval()` |

Performance is not a deciding factor: on a run of a few hundred files the difference is
around ten milliseconds. Twig is consistently a little *slower*, not faster — it compiles
to PHP and then runs it, with a thin runtime layer on top.

Two commonly assumed advantages do not survive checking:

- Twig does **not** avoid output buffering. `Twig\Template::render()` calls `ob_start()`
  and `ob_get_clean()` (`src/Template.php:178-192`); the buffering is merely hidden.
- Twig's default autoescaping is **wrong** for code generation. It HTML-escapes values
  interpolated into PHP source — `$x = 'O&#039;Brien'` — so `autoescape => false` is
  mandatory, and a code-aware escaper has to replace it.

What genuinely favours Twig here:

- **Templates as data.** Any loader — string, array, database — is first class. Native
  PHP templates come from files; holding one in a database means `eval()` or writing a
  temp file. This was the original reason for choosing Twig and it is a sound one.
- **Untrusted templates.** If a generator's templates become editable (which is where
  Gen-gen leads), a native-PHP template is arbitrary code execution. Twig's sandbox is a
  real answer; native PHP has none.
- **No tag collision.** A native-PHP template that generates PHP must escape its own
  opening tag (`<?php echo "<?php\n"; ?>`), because the literal text it wants to emit is
  also its own syntax. Twig has no such problem.

Two Twig footguns to configure around, both demonstrated:

- `strict_variables` is **off** by default, which is the cause of Extengen's current
  silent-empty-variable failures. It must be `true`. Native PHP emits an "Undefined
  variable" diagnostic by default, so as configured today native PHP is the safer of the
  two — and configured correctly Twig is safer still, since it throws rather than warns.
- Twig's in-process compiled-class reuse is keyed on template **source and name only, not
  on environment options**. Two Environments with the same source and name but different
  `autoescape` settings silently share the first one's compilation. Extengen currently
  builds a new Environment per fragment (`Generator::renderTemplateFragment`), which makes
  this latent rather than theoretical, and also costs the ~70% overhead visible in the
  table above.

Whichever engine wins, the escaping problem is the engine's blind spot: values
interpolated into generated PHP, XML or INI need a format-aware escaper, and neither
Twig's `autoescape` nor PHP's `<?= ?>` provides one. Emitters — as in plug-gen's
`PhpEmitter` / `XmlEmitter` / `IniEmitter` — are required either way, and matter more than
the engine choice.

**0.4 Introduce the Target abstraction.** `TargetInterface` names its structure
metamodel, its emitters and its template set. `Pipeline` becomes target-driven instead of
hardcoding one output type.
*Done when* a second, trivial fake target can be registered and run without touching the
pipeline.

**0.5 Golden-file test harness.** The comparison logic from plug-gen's `GoldenOutputTest`,
generalised into a reusable `TestCase`: comparison in both directions (every generated
file has a golden counterpart, and every golden file is still generated), fixture
auto-discovery, CRLF normalisation, plus a `generate-fixture` command to accept a
reviewed change.
*Done when* Exten-gen and Gen-gen obtain golden tests by extending one class.

**0.6 The shared Joomla library.** One installable library, `lib_yepr_gen`, holding
everything the generator extensions share: the engine and the third-party packages it
needs. Exten-gen, Meta-gen, Gen-gen, Plug-gen and whatever follows take their shared code
from that one copy rather than each carrying its own. The namespace prefix is `Yepr\Gen`,
and the engine is its first occupant at `Yepr\Gen\Core`.

*Our own* classes need no autoloading work. Joomla registers a library's namespace
automatically when the manifest declares one - verified in the Joomla 5 source:

- `libraries/namespacemap.php` builds `administrator/cache/autoload_psr4.php` from
  `getNamespaces('component' | 'module' | 'template' | 'plugin' | **'library'**)`.
- For libraries, `getExtensions()` scans `JPATH_MANIFESTS/libraries/*.xml` and reads the
  `<namespace path="...">` element, mapping it to `JPATH_LIBRARIES . '/<name>/<path>'`.
- The `extension - namespacemap` plugin rebuilds that file on
  `onExtensionAfterInstall`, `onExtensionAfterUninstall` and `onExtensionAfterUpdate`.

So `<namespace path="src">Yepr\Gen</namespace>` is the whole job for `Yepr\Gen\*`. JCB's
`PowerloaderHelper` and Extengen's
`require_once JPATH_LIBRARIES . '/yepr/vendor/autoload.php'` are both working around
something core already does, and neither pattern is carried forward.

*Third-party* packages are a separate matter, because a library manifest registers one
namespace and Twig's is not ours. They are resolved by composer at **build** time and
ship inside the library as `vendor/`, which is ordinary Joomla practice - the installer
not having composer is a packaging detail, not an argument against a composer dependency.
Registering that `vendor/autoload.php` is the library's own business, done lazily from
the one class that needs it, so no consumer ever writes a `require_once`. Regular Labs
does exactly this: `libraries/regularlabs/src/Image.php` requires the bundled autoloader
because it uses intervention/image, and nothing outside the library knows.

That is also the rule the engine-pluggability test enforces (see 0.3): `Twig\` may be
imported by the Twig renderer and by nothing else.

**Presence checking.** Each extension verifies the library on install and installs it when
missing or too old, from a copy carried inside its own package. This is the Regular Labs
and Akeeba pattern, and on this machine `pkg_modals.xml` shows the shape: the package
manifest does not declare the library at all; its `script.install.php` does the work.

No version negotiation is needed. The library is used only within this family of
extensions and all of it is developed in one place, so the check is simply: present and
recent enough, or install the copy carried in the package.

*Done when* the zip installs on a clean Joomla 6; a test component resolves `Yepr\Gen\*`
with no `require_once` and no composer at runtime; a test component resolves a vendored
third-party class; and installing that component on a site without the library brings the
library with it.

**0.7 Release 0.1.0.** Tag, build both artefacts, publish the update server.

---

## Stage 1 — Exten-gen

The large stage. Order matters: behaviour is captured before it is changed.

**1.1 Create the repository.** Mirror push from Extengen, preserving full history.
Restructure to a flat `src/` mirroring the site layout —
`src/administrator/components/com_extengen/`, not `src/com_extengen/administrator/...`.
Composer, CI, docs, `build/build.php`.
*Decision required here:* whether to filter `testForm.json` out of the history. This is
the only cheap moment to do it.

**1.2 Capture the current output as golden files.** Fixture models from the JSON dumps in
`xdiv/`; expected output from `generated/BalloonPlanning/` and `generated/Conference/`.
No code changes in this step.
*Done when* the suite regenerates today's output byte-identically, current bugs included.
That is the point: a baseline, not an endorsement.

**1.3 A real model object.** `ProjectModel` with `fromJson()` / `fromArray()`, a
`modelVersion`, and a validator, replacing the raw `stdClass` AST. This removes the eleven
copies of `initiateAST()`.
*Done when* nothing outside the model layer calls `json_decode` on `form_data`.

**1.4 Port the seven generators onto the core.** Output unchanged, golden files green
throughout. This is where the Joomla 6 component structure model gets designed —
discovered by porting generators that must produce working output, rather than drawn up
front. `AdminEntities` is the natural first candidate, having the most hand-rolled string
building.
*Done when* all generation runs through `Pipeline`, writes into a `FileCollection`, and
the golden files are unchanged.

**1.5 Fix what is broken.** From the analysis:

- `src/Field/LIonCore_M3/LanguageReferenceField.php` declares `class EntityReferenceField`
  with `$type = 'ClassifierReference'` — unloadable.
- `src/Factory/MVCFactory.php` carries the namespace `...\Administrator\Service` while
  living in `src/Factory/`, and `services/provider.php` registers Joomla's stock factory
  anyway. Fixing the path and wiring it in covers the dependency-injection item.
- Corrupted namespace attributes in `forms/metaProjectForms/`:
  `addfieldprefix="...\Administrator\Fieldclassifier.xml"` (3 occurrences), and
  `Yepr\\Component\\Extengen\\\Administrator\\MetaProjectForm\\...` in `projectForm.xml`,
  pointing at an empty directory tree.
- Site-side templates under `generator_templates/Joomla4/component/components/` emit
  `Administrator\View` and `Administrator\Model` namespaces; generated front-end views
  cannot autoload.
- Dead `test.json` reads in `View/ERD/HtmlView.php:48` and
  `View/FormsDiagram/HtmlView.php:48`.

**1.6 Real packaging.** A package manifest covering component, media and the library
dependency; `build.php` producing an installable zip; generation output delivered as a
downloadable zip rather than files written under `generated/`.
*Done when* a generated component installs on a clean Joomla 6 from the zip Exten-gen
produces.

**1.7 Joomla 6 sweep — Exten-gen itself.** `JHtmlSidebar` (6 files), `Factory::getUser`
(7 files), `CMSObject` (4 files), `getError()` / `setError()` (6 files).

**1.8 Joomla 6 sweep — the generated output.** Templates to current APIs. Bound
parameters in generated list-model queries, which currently interpolate with
`$db->quote()`. Removal of the commented-out Akeeba ATS blocks, and of the roughly 130
unreferenced files under `generator_templates/` (akeeba layouts, plugin skeletons, an
entire `template/` folder) — only 8 distinct template names are actually rendered. Every
change arrives as a golden-file diff to be reviewed.

**1.9 The reference-field rework.** The model is emitted once as JSON into the page;
reference fields become custom elements reading one in-memory model. The roughly 300
deprecated lines in `admin-project.js` go, along with the dead `editChildConceptList` and
`editConceptFieldsList` call sites in the form XML.
*Done when* adding an entity and referencing it works without saving first, with a Cypress
spec proving it.

**1.10 Custom code.** Model-side slots as the primary mechanism, with
`ProtectedRegionMerger` as a safety net for edits made in generated output. Designed so
that a slot can later be replaced by a nested sub-model that generates its content.

**1.11 Quality gate.** PHPStan clean at an agreed level, Cypress specs for the main flows,
and a test that generated PHP actually parses.
*Decision required:* the PHPStan level.

**1.12 Release 1.0.0.** The update server moves to the new repository. The installed
extension keeps the element name `com_extengen`.

**Scope of v1.** Entities with properties, n:1 and n:n relations, embeddables, index pages
with filters, detail pages with edit fields, language files, an installable package.
*Decision required:* admin-only for 1.0 with the front-end at 1.1, or both from the start.
Admin-only is a real scope cut.

**Named gaps in the v1 model.** Three things the model cannot express, written down here
because each is invisible until something needs it and then blocks a whole line of work.

| Gap | Model today | Bites at |
|---|---|---|
| A custom form field type | `editfield.xml` picks among *stock* Joomla types through `htmltype`. A generated extension cannot declare a field type of its own. | 4.2 |
| A custom validation rule | Nothing. Extengen's own `LetterRule` has no counterpart in the model. | 4.2 |
| Tabs and subform layouts on a generated form | Nothing. Generated forms are one fieldset. | 4.2 |

None of the three blocks v1, and that is worth stating rather than assuming: the current
generator emits only stock field types - `text`, `sql` for a relation, `hidden`, `number`,
`calendar`, `list`, `subform`, `editor` - so a generated CRUD component needs no field
class of its own. What the gaps block is **Exten-gen generating itself**, because its own
forms use eleven custom field classes and a rule.

How many of those eleven survive is not fixed yet. Step 1.9 replaces the reference-field
mechanism with one client-side element driven by the model, which is precisely what most
of those eleven classes do by hand. The size of this gap should therefore be re-measured
after 1.9 rather than estimated now.

---

## Stage 2 — Gen-gen

Possible only now, because both endpoints exist: an explicit source model from 1.3 and an
explicit target structure model from 1.4.

**2.1 Extract the transformation rules into data.** Separate *what maps to what* from
*how it is written out*. The mapping becomes a structure; the emitters stay code.

**2.2 Model the generator.** Forms for transformation rules, MPS-style: source pattern to
target structure, with conditions and iteration.

**2.3 Generate a generator, and check it.** Acceptance criterion: byte-identical output to
the hand-written generator it replaces, measured against Stage 1's golden files. Not a
judgement call.

**2.4 Repository, package, release.**

---

## Stage 3 — Meta-gen

**3.1 Repair or rebuild the LionWeb model.** The concept forms work; the field classes and
namespace prefixes around them do not (see 1.5).

**3.2 The forms generator.** Concept model to form XML, reference field elements, and the
JavaScript the 1.9 mechanism needs.

**3.3 Round-trip proof.** Model ER1 in LionWeb, regenerate Exten-gen's own forms, and
compare against the hand-written ones as golden files.

**3.4 Repository, package, release.**

---

## Stage 4 — convergence

**4.1 Plug-gen adopts the core**, dropping its private copy.

**4.2 Close the model gaps that block self-hosting.** The three named under Stage 1: a
custom form field type, a custom validation rule, and tabs or subform layouts on a
generated form. Re-measure first - 1.9 may have removed most of the need - then add only
what is still missing.

**4.3 Self-hosting.** Exten-gen generates Exten-gen. Everything it needs exists by now:
the engine from Stage 0, working generation from Stage 1, modelled generators from Stage
2, generated forms from Stage 3 and the model gaps closed in 4.2. The criterion is
byte-identical output against the hand-written component, the same way 2.3 checks a
modelled generator.

**4.4 A second target**, Drupal or WordPress, which is the real proof that 0.4 was done
correctly.

---

## Decisions outstanding

Each is flagged at the step where it bites.

| Step | Decision |
|---|---|
| 1.1 | Whether to filter `testForm.json` out of the history during the mirror push |
| 1.11 | PHPStan level |
| 1.12 | Front-end in v1.0, or deferred to v1.1 |

## Suggested entry point

**0.1.** Self-contained, unblocks everything downstream, and touches nothing the current
component depends on.
