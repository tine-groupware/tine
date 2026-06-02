/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * get list of all builds / files to include
 *
 * NOTE: with webpack-dev-server the files to include do not exists on the disk,
 *       so we can't check for file_exists in PHP code. Therefore we ask webpack
 *       which files/builds exist.
 */

import config from'./webpack.config.js';

const resolvedConfig = await config();
const filesToInclude = [];

Object.keys(config.entry).forEach(function (bundle) {
    filesToInclude.push(bundle + '-FAT.js');
});

export default filesToInclude;