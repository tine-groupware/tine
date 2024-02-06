/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const path = require('path');
const baseDir = path.resolve(__dirname , '../../');

/**
 * moves unnamed chunks into "<app>/js/" dir
 * with this a chunkFilename in import() / require.ensure() calls is not longer needed
 */
class ChunkNamePlugin {
    apply(compiler) {
        compiler.hooks.compilation.tap(
            "ChunkNamePlugin",
            (compilation, { normalModuleFactory }) => {
                compilation.hooks.beforeChunkIds.tap(
                    "ChunkNamePlugin",
                    (chunks) => {
                        chunks.forEach((chunk) => {
                            if (! chunk.name) {
                                const request = chunk.getModules()[0].userRequest.replace(baseDir, '');
                                const moduleApp = request.split('/')[1];
                                chunk.name = `${moduleApp}/js/` + request.replace(`/${moduleApp}/js/`, '').replace(/[/.]/g, '_');
                            }
                        });
                    }
                );
            }
        );
    }
}

module.exports = ChunkNamePlugin;
