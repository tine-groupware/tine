/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * setup webpack entry
 */
import(
    /* webpackChunkName: "Tinebase/js/tinebase" */
    'tinebase.js'
).then(function (libs) {
    libs.lodash.assign(window, libs);
    require('tineInit');
    require('./init');
});