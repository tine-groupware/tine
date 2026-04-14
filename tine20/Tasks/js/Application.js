/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tasks');

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.Application
 * @extends     Tine.Tinebase.Application
 * Tasks Application Object <br>
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tasks.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Task')
     */
    addButtonText: 'New Task',
});
