/**
 * Projectform specific JS forLIonCore_M3
 *
 * @copyright  Copyright (C) 2023+, Yepr, Herman Peeren. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Per meta-language we have an objectReferencerMap:
// a map of all objects that can be referenced and the object (referencer), possibly higher in the hierarchy, that has the name and id.
// If the referencer is the same as the referenced object, then it is left out of this map.
//
// Here is the map in LIonCore_M3, used to define other languages:
let objectReferencerMap = new Map;
objectReferencerMap.set('Concept', 'LanguageEntity');
objectReferencerMap.set('ConceptInterface', 'LanguageEntity');
objectReferencerMap.set('Annotation', 'LanguageEntity');
objectReferencerMap.set('DataType', 'LanguageEntity');
// There are no references to Features in LIonCore_M3
/*
objectReferencerMap.set('Property', 'Feature');
objectReferencerMap.set('Reference', 'Feature');
objectReferencerMap.set('Containment', 'Feature');*/
