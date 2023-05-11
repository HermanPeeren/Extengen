# Extengen
Extension generator as Joomla extension, model based on eJSL (JooMDD), with projectional editor. 

First the JooMDD model was ported to Jetbrain's MPS (eJSL-MPS) and based on that structure this was turned into a Joomla extension, with HTML forms as input of the AST.

# Information about the Extension Generator project
11-5-2023

Planned features will be adjusted in the process.

### version 0.8.0

Current version - under construction.

Plain component backend generated.

### version 0.8.1

Minimal Viable Product

features:

* plain component frontend generated
* fill entities & attributes dropdowns in extengen backend
* get rid of fields and associations in the Extension Generator
* clean the mysql-files of com_extengen
* install generated component in current Joomla 4 site (for testing)

### version 0.9.0

features:

* generator-generator: define and adjust generators
* project forms generator: define and adjust project forms


### version 1.0.0

features:

* toggle Joomla core features in generated extension: categories, tags, versioning, workflow, pagination, custom fields, ordering, access control, language associations, routing/alias, action logs, finder
* submenu for this component
* toggle translations and choose translation service
* override translations
* add categories to projects and generators
* add dashboard page type
* update-site for extengen; update from github

### version 1.1.0

features:

* one-to-many relations
* add page-type detail with indices (for the multiple files)

### version 1.2.0

features:

* save versions on Git(hub)
* update extengen info page(s) from github instead of shipping with component

### version 1.3.0

features:

* generate modules
* generate plugins
* CLI and API applications

### version 1.4.0

features:

* import & export projects
* migrate older version projects

### version 2.0.0

features:

* Initial WordPress generator

### version 2.1.0

features:

* Joomla4 + Doctrine generator

### version 2.2.0

features:

* possibility to use Event Sourcing in projects
* Joomla4 + Prooph Event Sourcing generator

 

 
