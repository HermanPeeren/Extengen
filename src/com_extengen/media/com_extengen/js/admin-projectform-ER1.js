/**
 * Projectform specific JS for ER1
 *
 * @copyright  Copyright (C) 2023+, Yepr, Herman Peeren. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Per meta-language we have an objectReferencerMap:
// a map of all objects that can be referenced and the object (referencer), possibly higher in the hierarchy, that has the name and id
// If the referencer is the same as the referenced object, then it is left out of this map.
//
// Here is the map in ER1, used to define (CRUD-)Joomla-extensions:

let objectReferencerMap = new Map;
objectReferencerMap.set('IndexPage', 'Page');
objectReferencerMap.set('DetailsPage', 'Page');
// There is no map necessary for Properties and References, for we only refer to Fields in general
/*objectReferencerMap.set('Property', 'Field');
objectReferencerMap.set('Reference', 'Field');*/
