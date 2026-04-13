/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

Tine.Timetracker.registerAccountables = function() {
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('AccountableRegistry')) {
        Tine.Timetracker.registerAccountables.defer(10);
        return false;
    }
    
    Tine.Sales.AccountableRegistry.register('Timetracker', 'Timeaccount');
};

Tine.Timetracker.registerAccountables();
