/*
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Courses');

Tine.Courses.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Course',
    contentTypes: [
        {modelName: 'Course',  requiredRight: null, singularContainerMode: true}
    ]
});
