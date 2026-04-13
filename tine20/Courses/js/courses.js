/*
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../styles/Courses.scss';
import './MainScreen';
import './CourseFilterPanel';
import './Models';
import './CourseGridPanel';
import './AddMemberDialog';
import './CourseEditDialog';

Tine.Courses.coursesBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'Course',
    recordClass: Tine.Courses.Model.Course,

    /**
     * deletes multiple records identified by their ids
     *
     * @param   {Array} records Array of records or ids
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success
     */
    deleteRecords: function(records, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 's';
        options.params.ids = this.getRecordIds(records);

        // increase timeout to 20 minutes
        options.timeout = 1200000;

        return this.doXHTTPRequest(options);
    }
});

/**
 * default backend
 */
Tine.Courses.courseTypeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Courses',
    modelName: 'CourseType',
    recordClass: Tine.Courses.Model.CourseType
});