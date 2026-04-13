/*
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

Ext.ns('Tine.Poll');

/**

* @namespace   Tine.Poll

* @class       Tine.Poll.Application

* @extends     Tine.Tinebase.Application

* Poll Application Object <br>

*

* @author      Christian Feitl <c.feitl@metaways.de>

*/

Tine.Poll.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: true,



    /**
     * Get translated application title of the application
     *
     * @return {String}
     */
    getTitle: function () {
        return this.i18n.ngettext('Poll', 'Polls', 1);
    }
});
