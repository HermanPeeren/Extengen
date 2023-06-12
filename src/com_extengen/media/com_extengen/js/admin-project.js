/**
 * @copyright  Copyright (C) 2023, Yepr, Herman Peeren. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

let conceptReferences = [];
let conceptOptions    = [];

// childConceptReferences and childConceptOptions are 3-dimensional arrays:
//     1. first index:  the parent concept name
//     2. second index: the id of the instance of the parent concept
//     3. third index:  the child concept
let childConceptReferences = [];
let childConceptOptions    = [];


// To be removed ------------------
let entities      = [];
let entityOptions = [];
let pages         = [];
let pageOptions   = [];
// fields and fieldOptions are 2-dimensional arrays, with first index an entity_id
let fields        = {};
let fieldOptions  = [];
// END of to be removed--------------

/**
 * Set the options of all reference dropdowns for a concept.
 * 
 */
function setConceptRefOptions(conceptName) {
  // get all references to dropdown references for this concept
  let conceptRefs = [].slice.call(document.querySelectorAll('.' + conceptName + 'Ref'));
  conceptRefs.forEach(conceptRef => {

    // the selected value is also in the hidden reference_id-field, even if the options are not yet restored
    let selected = document.getElementById(conceptRef.id + '_id');

    // Put the options in the select-box
    conceptRef.innerHTML = conceptOptions[conceptName];

    // and restore the value (will only be restored if value is in options)
    conceptRef.value=selected.value;
  });
}

/**
 * Set the options of all reference dropdowns for a child concept. todo: from entities & fields to parentConcept & childConcept
 */
function setChildConceptRefOptions(childConceptName, parentConceptName) {
  // get all references to fields dropdowns
  let fieldRefs = [].slice.call(document.querySelectorAll('.FieldRef'));
  fieldRefs.forEach(fieldRef => {

    // the selected value is also in the hidden reference_id-field, even if the options are not yet restored
    let selected = document.getElementById(fieldRef.id + '_id');

    // Get the entity_id of this field-list reference
    let entity_id = document.getElementById(fieldRef.id.replace('__field_reference', '__entity_reference_id')).value;

    // Put the options in the select-box
    fieldRef.innerHTML = childConceptOptions[parentConceptName][entity_id];

    // and restore the value (will only be restored if value is in options)
    fieldRef.value=selected.value;
  });
}

/**
 * Update list of references to a concept when concept name changes (or is deleted or added)
 */
function updateConceptReferencesList(conceptName) {

  conceptReferences[conceptName]=[].slice.call(document.querySelectorAll('.' + conceptName.toLowerCase() + 'Name'));

  // get the <concept>_id of every instance of the current <concept>, creating an uuid if <concept>_id doesn't exist
  concept_ids = [];
  conceptReferences[conceptName].forEach(instanceName => {
    // for-var ....<concept>_name has a hidden var ....<concept>_id
    concept_id = document.getElementById(instanceName.id.replace(conceptName.toLowerCase() + '_name', conceptName.toLowerCase() + '_id'));

    // give that concept_id an uuid if empty
    if (!concept_id.value) {
      concept_id.value = uuidv4();
    }

    concept_ids.push(concept_id);
  });

  // get the <concept>_type of every instance of the current <concept>. Will be null if <concept>_type doesn't exist
  concept_types = [];
  conceptReferences[conceptName].forEach(instanceName => {
    // for-var ....<concept>_name has a hidden var ....<concept>_id
    concept_type = document.getElementById(instanceName.id.replace( conceptName.toLowerCase()+'_name',  conceptName.toLowerCase()+'_type'));

    let type = "";
    if (concept_type != null)
    {
      type=concept_type.value + ": ";
    }

    concept_types.push(type);
  })


  // make the options with concept_ids as values and concept_names visible in the list
  conceptOptions[conceptName] = '<option value="0">&nbsp;</option>';
  for (let i=0; i<conceptReferences[conceptName].length;i++) {
    conceptOptions[conceptName] += `<option value=${concept_ids[i].value}>${concept_types[i] 
                                    + conceptReferences[conceptName][i].value}</option>` + '\n';
  }

  setConceptRefOptions(conceptName);

}

/**
 * Update list of ALL references to a child concept.
 * For instance: list of references to fields (= child concept) for all entities (= parent concept)
 *
 */
function updateAllChildConceptReferencesList(childConceptName, parentConceptName) {

  // Empty child-concepts-list for this parent concept
  childConceptReferences[parentConceptName] = [];

  // Get all child-concept-fields from this form // todo: each child concept must be unique within the parent scope, but not beyond!!!
  const childConceptsAll=[].slice.call(document.querySelectorAll('.' + childConceptName.toLowerCase() + 'Name'));

  // Put the <child concept>_name and <child concept>_id of every <child concept> in the <child concept>-array, per <parent concept>_id
  // The entity_id is also added for easy reference when building the options-html
  childConceptsAll.forEach(childConceptField =>
  {
    const parent_id = getParentIdForChildConcept(childConceptField);
    if (!(parent_id in childConceptReferences[parentConceptName])) childConceptReferences[parentConceptName][parent_id] = [];
    childConceptReferences[parentConceptName][parent_id].push({fieldName:childConceptField.value, fieldId:getChildConceptId(childConceptField), parentId:parent_id});
  })

  // Update fieldS options for all entities
  updateAllChildConceptOptions(childConceptName, parentConceptName);
}

/**
 * Get the <childConcept>_id of this <childConcept>Name-field.
 * A <childConcept>Name-field has a <childConcept>_name; the corresponding <childConcept>_id has '<childConcept>_id' on that spot.
 *
 */
function getChildConceptId(childConceptField) {
  // This <childConcept> should have a <childConcept>-id todo: the name of the childConcept instead of "field" (at the moment that name is "field")
  const field_id = document.getElementById(childConceptField.id.replace('field_name', 'field_id'));

  // give that <childConcept>_id an uuid if empty
  if (!field_id.value) {
    field_id.value = uuidv4();
  }

  return field_id.value;
}

/**
 * Get the entity_id of the entity this fieldName-field (= property) belongs to.
 * A fieldName-field has a 'property_name'; the corresponding entity_id has 'entity_id' on that spot
 * In the data-definition a field (= a property) belongs to a specific entity
 * todo: maybe a field should be a property OR a reference! For now it is only a property...
 */
function getParentIdForChildConcept(childConceptField) {
  // This field should have an entity-id for the entity it belongs to todo: replace property and entity with the proper names of the child and parent
  entity_id = document.getElementById(childConceptField.id.replace('field_name', 'entity_id'));

  // If still empty, then find the entity_id of the entity it belongs to
  if (!entity_id.value)
  {
    fieldId = childConceptField.id;
    firstPart = fieldId.substr(0, fieldId.indexOf("_field__field"))
    entity_id.value = document.getElementById(firstPart + "_entity_id").value;
  }

  return entity_id.value;
}

/**
 * Update all child concept options for this child concept
 * For instance: update all field-options for entities
 */
function updateAllChildConceptOptions(childConceptName, parentConceptName) {

  // Empty fieldsOptions-list
  childConceptOptions[parentConceptName] = [];

  // Put the options with <child concept>_ids as values and <child concept>_names visible in the list in childConceptOptions-list per <parent_concept>_id
  for (let parent_id in childConceptReferences[parentConceptName])
  {
    let parent = childConceptReferences[parentConceptName][parent_id];
    let parentChildOptions = '<option value="0">&nbsp;</option>';
    for (let i=0; i<parent.length;i++)
    {
      parentChildOptions += `<option value=${parent[i].fieldId}>${parent[i].fieldName}</option>` + '\n';
    }

    childConceptOptions[parentConceptName][parent_id] = parentChildOptions;
  }

  // Put the options in the document
  setChildConceptRefOptions(childConceptName, parentConceptName);

}

/**
 * Update list of references for given parent entity when child entity name changes
 */
function editChildConceptList(field) {

  // Get the field_id for this field
  //const field_id = getfieldId(field);

  // Get the entity-id for the entity this field belongs to
  //const entity_id = getEntityIdForfield(field);

  // TODO: Update the fields-list and the options in the fields-dropdown for only this entity

  // temporary: Update ALL field-lists and options in the fields-dropdowns
  // todo: get the childConceptName and parentConceptName
  const childConceptName  = 'Field';
  const parentConceptName = 'Entity';
  updateAllChildConceptReferencesList(childConceptName, parentConceptName);

}

// put the changed ID of a referred to concept (from drop down select) in the hidden field
function backupConceptID(event) {
  // If the list was still empty: fill it...
  if (event.target.value==999999)
  {
    const type = event.target.type
    const conceptName = type.substring(0,type.search('Reference'));
    updateConceptReferencesList(conceptName);
    return;
  }

  // the hidden field with the reference_id has the same id as the select box that triggered this function + _id
  reference_id = document.getElementById(event.target.id + '_id');
  reference_id.value = event.target.value;

  // todo: get the childConceptName and parentConceptName
  const childConceptName  = 'Field';
  const parentConceptName = 'Entity';

  // After updating a reference to a parent-concept, we might set the options for the children (if any)
  // For instance after updating a reference to an entity, we might update a fields-dropdown
  // todo: check if there is a child for this select-box AND only update the fields of that child-dropdown
  updateAllChildConceptOptions(childConceptName, parentConceptName);
}

// put the changed ID of a referred to field (from drop down select) in the hidden field
function backupChildConceptID(event) {
  // If the list was still empty: fill it...
  if (event.target.value==999999)
  {
    // todo: get the childConceptName and parentConceptName
    const childConceptName  = 'Field';
    const parentConceptName = 'Entity';

    editChildConceptList(event.target);
    return;
  }

  // the hidden field with the field_reference_id has the same id as the select box that triggered this function + _id
  reference_id = document.getElementById(event.target.id + '_id');
  reference_id.value = event.target.value;
}

// simple uuid-function
function uuidv4() {
  return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
      (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
  );
}

window.onload = function () {

  // Init concepts list
  // We hardcode 2 concepts to refer to here: Entity and Page. Entity has a child concept field. Todo: get all concepts from meta-model
  let conceptNames = [['Entity', ['field']], ['Page']];

  // Init concept references list
  conceptNames.forEach(conceptName => {
    // Put each concept as entry in the array of concept references and concept options
    conceptReferences[conceptName[0]] = [];
    conceptOptions[conceptName[0]] = [];

    // And to store possible child concept references and the child concept options
    childConceptReferences[conceptName[0]] = [];
    childConceptOptions[conceptName[0]] = [];

    // Init all references to this concept. For instance: references to entities.
    updateConceptReferencesList(conceptName[0]);


    // Add child concepts if available
    if (conceptName.length>1)
    {
      childConceptReferences[conceptName[0]] = [];
      conceptName[1].forEach(childConceptName => {
        childConceptReferences[conceptName[0]][childConceptName] = [];

        // Init all references to this child concept. For instance: references to all fields of entities.
        updateAllChildConceptReferencesList(childConceptName, conceptName[0]);
      });
    }
  });

  // TODO: this can then go
  /*editEntitiesList();
  editPagesList();
  editfieldsList();*/
};



// ------------------------Under this line is DEPRECATED and should be removed once the above code works.---------------

/**
 * Set the options of all ENTITY reference dropdowns
 */
function setRefOptions() {
  // get all references to entities dropdowns
  let entityRefs = [].slice.call(document.querySelectorAll('.entityRef'));
  entityRefs.forEach(entityRef => {

    // the selected value is also in the hidden reference_id-field, even if the options are not yet restored
    let selected = document.getElementById(entityRef.id + '_id');

    // Put the options in the select-box
    entityRef.innerHTML = entityOptions;

    // and restore the value (will only be restored if value is in options)
    entityRef.value=selected.value;
  });
}

/**
 * Set the options of all field reference dropdowns
 */
function setfieldRefOptions() {
  // get all references to entities dropdowns
  let fieldRefs = [].slice.call(document.querySelectorAll('.fieldRef'));
  fieldRefs.forEach(fieldRef => {

    // the selected value is also in the hidden reference_id-field, even if the options are not yet restored
    let selected = document.getElementById(fieldRef.id + '_id');

    // Get the entity_id of this field-list reference
    let entity_id = document.getElementById(fieldRef.id.replace('__field_reference', '__entity_reference_id')).value;

    // Put the options in the select-box
    fieldRef.innerHTML = fieldOptions[entity_id];

    // and restore the value (will only be restored if value is in options)
    fieldRef.value=selected.value;
  });
}

/**
 * Set the options of all PAGE reference dropdowns
 */
function setPageRefOptions() {
  // get all references to pages dropdowns
  let pageRefs = [].slice.call(document.querySelectorAll('.pageRef'));
  pageRefs.forEach(pageRef => {

    // the selected value is also in the hidden reference_id-field, even if the options are not yet restored
    let selected = document.getElementById(pageRef.id + '_id');

    // Put the options in the select-box
    pageRef.innerHTML = pageOptions;

    // and restore the value (will only be restored if value is in options)
    pageRef.value=selected.value;
  });
}

/**
 * Update ENTITIES list when entity name changes
 */
function editEntitiesList() {

  entities=[].slice.call(document.querySelectorAll('.entityName'));

  // get the entity_id of every entity, creating an uuid if entity_id doesn't exist
  entity_ids = [];
  entities.forEach(entityName => {
    // for-var ....entity_name has a hidden var ....entity_id
    entity_id = document.getElementById(entityName.id.replace('entity_name', 'entity_id'));

    // give that entity_id an uuid if empty
    if (!entity_id.value) {
      entity_id.value = uuidv4();
    }

    entity_ids.push(entity_id);
  })


  // make the options with entity_ids as values and entity_names visible in the list
  entityOptions = '<option value="0">&nbsp;</option>';
  for (let i=0; i<entities.length;i++) {
    entityOptions += `<option value=${entity_ids[i].value}>${entities[i].value}</option>` + '\n';
  }

  setRefOptions();

}

/**
 * Get the field_id of this fieldName-field
 * A fieldName-field has a 'property_name; the corresponding property_id has 'property_id' on that spot
 * todo: maybe a field should be a property OR a reference! For now it is only a property...
 */
function getPropertyId(field) {
  // This field should have a property-id
  const property_id = document.getElementById(field.id.replace('property_name', 'property_id'));

  // give that field_id an uuid if empty
  if (!property_id.value) {
    property_id.value = uuidv4();
  }

  return property_id.value;
}

/**
 * Get the entity_id of the entity this fieldName-field (= property) belongs to.
 * A fieldName-field has a 'property_name'; the corresponding entity_id has 'entity_id' on that spot
 * In the data-definition a field (= a property) belongs to a specific entity
 * todo: maybe a field should be a property OR a reference! For now it is only a property...
 */
function getEntityIdForProperty(field) {
  // This field should have an entity-id for the entity it belongs to
  entity_id = document.getElementById(field.id.replace('property_name', 'entity_id'));

  // If still empty, then find the entity_id of the entity it belongs to
  if (!entity_id.value)
  {
    fieldId = field.id;
    firstPart = fieldId.substr(0, fieldId.indexOf("_property__property"))
    entity_id.value = document.getElementById(firstPart + "_entity_id").value;
  }

  return entity_id.value;
}

/**
 * Update fieldS (= properties) list for all entities *
 * todo: maybe a field should be a property OR a reference! For now it is only a property...
 */
function updateAllfields() {

  // Empty fields-list
  fields = [];

  // Get all fieldname-fields from this form
  const fieldsAll=[].slice.call(document.querySelectorAll('.fieldName'));

  // Put the property_name and property_id of every field in the fields-array, per entity_id
  // The entity_id is also added for easy reference when building the options-html
  fieldsAll.forEach(field =>
  {
    const entity_id = getEntityIdForProperty(field);
    if (!(entity_id in fields)) fields[entity_id] = [];
    fields[entity_id].push({fieldName:field.value, fieldId:getPropertyId(field), entityId:entity_id});
  })

  // Update fieldS options for all entities
  updateAllfieldsOptions();
}

/**
 * Update fieldS options for all entities
 */
function updateAllfieldsOptions() {

  // Empty fieldsOptions-list
  fieldsOptions = [];

  // Put the options with field_ids as values and field_names visible in the list in fieldsOptions-list per entity_id
  for (let entity_id in fields)
  {
    let entity = fields[entity_id];
    let entityfieldOptions = '<option value="0">&nbsp;</option>';
    for (let i=0; i<entity.length;i++)
    {
      entityfieldOptions += `<option value=${entity[i].fieldId}>${entity[i].fieldName}</option>` + '\n';
    }

    fieldOptions[entity_id] = entityfieldOptions;
  }

  // Put the options in the document
  setfieldRefOptions();

}

/**
 * Update fieldS list for given entity when field name changes
 */
function editfieldsList(field) {

  // Get the field_id for this field
  //const field_id = getfieldId(field);

  // Get the entity-id for the entity this field belongs to
  //const entity_id = getEntityIdForfield(field);

  // TODO: Update the fields-list and the options in the fields-dropdown for only this entity

  // temporary: Update ALL field-lists and options in the fields-dropdowns
  updateAllfields();

}


/**
 * Update PAGES list when something changes
 */
function editPagesList() {

  pages=[].slice.call(document.querySelectorAll('.pageName'));

  // get the page_id of every page, creating an uuid if page_id doesn't exist
  page_ids   = [];
  page_types = [];
  pages.forEach(pageName => {
    // for-var ....page_name has a hidden var ....page_id
    page_id   = document.getElementById(pageName.id.replace('page_name', 'page_id'));

    // give that page_id an uuid if empty
    if (!page_id.value) {
      page_id.value = uuidv4();
    }

    page_ids.push(page_id);

    // and the page type
    page_types.push(document.getElementById(pageName.id.replace('page_name', 'page_type')));
  })


  // make the options with page_ids as values and page_names visible in the list
  pageOptions = '<option value="0">&nbsp;</option>';
  for (let i=0; i<pages.length;i++) {
    pageOptions += `<option value=${page_ids[i].value}>${page_types[i].value+': ' + pages[i].value}</option>` + '\n';
  }

  setPageRefOptions();
}

// TODO: following 3 functions  are almost the same -> refactor
// put the changed ID of a referred to ENTITY (from drop down select) in the hidden field
function backupEntityID(event) {
  // If the list was still empty: fill it...
  if (event.target.value==999999)
  {
    editEntitiesList();
    return;
  }

  // the hidden field with the reference_id has the same id as the select box that triggered this function + _id
  reference_id = document.getElementById(event.target.id + '_id');
  reference_id.value = event.target.value;

  // todo: only for this entity
  updateAllfieldsOptions();
}

// put the changed ID of a referred to field (from drop down select) in the hidden field
function backupfieldID(event) {
  // If the list was still empty: fill it...
  if (event.target.value==999999)
  {
    editfieldsList();
    return;
  }

  // the hidden field with the field_reference_id has the same id as the select box that triggered this function + _id
  reference_id = document.getElementById(event.target.id + '_id');
  reference_id.value = event.target.value;
}

// put the changed ID of a referred to PAGE (from drop down select) in the hidden field
function backupPageID(event) {
  // If the list was still empty: fill it...
  if (event.target.value==999999)
  {
    editPagesList();
    return;
  }

  // the hidden field with the reference_id has the same id as the select box that triggered this function + _id
  reference_id = document.getElementById(event.target.id + '_id');
  reference_id.value = event.target.value;
}



