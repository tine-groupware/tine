/*
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Courses');

Tine.Courses.CourseFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Courses.CourseFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Courses.CourseFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Courses_Model_CourseFilter'}]
});

