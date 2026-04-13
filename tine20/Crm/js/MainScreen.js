/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Crm');

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.MainScreen
 * @extends Tine.widgets.MainScreen
 * MainScreen of the Crm Application <br>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @constructor
 * Constructs mainscreen of the crm application
 */
Tine.Crm.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Lead',
    contentTypes: [
        {modelName: 'Lead', requiredRight: null, singularContainerMode: false}
    ]
});
