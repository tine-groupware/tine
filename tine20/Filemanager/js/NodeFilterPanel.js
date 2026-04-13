/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.NodeFilterPanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    app: 'Filemanager',
    contentType: 'Node',
    filter: [{field: 'model', operator: 'equals', value: 'Filemanager_Model_Node'}]
});

/**

* download file/folder into browser

*

* @param record

* @param revision

* @returns {Ext.ux.file.Download}

*

*/
