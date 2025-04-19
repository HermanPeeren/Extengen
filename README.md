# Extengen
Extension generator as Joomla extension, model based on eJSL (JooMDD), with projectional editor. 

First the JooMDD model was ported to Jetbrain's MPS (eJSL-MPS) and based on that structure this was turned into a Joomla extension, with HTML forms as input of the AST.

# Information about the Extension Generator project
19-4-2025

At the moment mainly working on getting the meta-level complete, using forms:

* generator-generator: define and adjust generators
* project forms generator: define and adjust project forms

Still in the phase to get the first official release out. So be warned: the Extension Generator is still not production-ready.

### version 0.9.0
If the model and the generator can both be created/edited, using forms, then the model and generator will be much easier
to adjust. That's why I postponed all kinds of changes in model and generator until after this version.


### version 1.0.0
With this version basic Joomla components will be easy to make.

Features of model and generator, that will be added via the meta-level:

* toggle Joomla core features in generated extension: categories, tags, versioning, workflow, pagination, custom fields, ordering, access control, language associations, routing/alias, action logs, finder
* automatic junction table for n:n relations
* submenu for this component
* toggle translations and choose translation service; override translations
* add dashboard page type
* update-site for extengen; update from github

### future version features
* import & export projects
* migrate older version projects
* save versions of the model and generator on Git(hub)
* add page-type detail with indices (for the multiple files)
* update extengen info page(s) from github instead of shipping with component
* generate Joomla modules
* generate Joomla plugins
* Joomla CLI and API applications
* Initial WordPress generator
* Joomla + Doctrine generator
* possibility to use Event Sourcing in projects
* Joomla + Prooph Event Sourcing generator


 
