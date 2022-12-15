/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * webpack tine 2.0 app-loader
 *
 * - does not really load a source format
 *   -> it's just there to inject initial javascript of the apps
 * - finds all apps at BUILDTIME so their javascript code gets build
 * - injects a RUNTIME app loader so application js is only included on demand
 */
var fs = require('fs');
var _ = require('lodash');
var path = require('path');

var baseDir  = path.resolve(__dirname , '../../'),
    initialAppFileMap = {};

// find initial js for all available apps at BUILDTIME!
fs.readdirSync(baseDir).forEach(function(baseName) {
    var initialFile = '';

    try {
        // try npm package.json
        var pkgDef = JSON.parse(fs.readFileSync(baseDir + '/' + baseName + '/js/package.json').toString());
        initialFile = baseDir + '/' + baseName + '/js/' + (pkgDef.main ? pkgDef.main : 'index.js');
    } catch (e) {
        // fallback to legacy jsb2 file
        var jsb2File =  baseDir + '/' + baseName + '/' + baseName + '.jsb2';
        if (!initialFile) {
            try {
                if (fs.statSync(jsb2File).isFile()) {
                    initialFile = jsb2File;
                }
            } catch (e) {
            }
        }
    }

    if (initialFile) {
        initialAppFileMap[baseName + '/js/' + baseName] = initialFile;
    }
});

// console.log(JSON.stringify(initialAppFileMap, null, 2));

// create RUNTIME app loader
module.exports = function() {
    this.cacheable();

    var runtime = '';

    runtime += 'var _ = window.lodash,\n';
    runtime += '     availableApps = ' + JSON.stringify(initialAppFileMap, null, 2) + ',\n';
    runtime += '     appResolves = {},\n';
    runtime += '     appRejects = {},\n';
    runtime += '     appLoadedPromises = {};\n';
    runtime += '     appLoadedStates = {};\n';
    runtime += '\n';
    runtime += '_.each(availableApps, function(index,key) {\n';
    runtime += '  var appName = key.replace(/\\/.*$/, "");\n';
    runtime += '  appLoadedStates[appName] = "pending";\n';
    runtime += '  appLoadedPromises[appName] = new Promise(function(resolve, reject) {\n';
    runtime += '    appResolves[appName] = resolve;\n';
    runtime += '    appRejects[appName] = reject;\n';
    runtime += '  });\n';
    runtime += '});\n';
    runtime += '\n';


    runtime += 'module.exports = {\n';
    runtime += '  loadAllApps: function(userApps) {\n';

    runtime += '    var pms = _.reduce(userApps, function(p, app) {\n';

    // runs at buildtime
    _.each(initialAppFileMap, function(index, key) {
        runtime += '      var appName = "' + key.replace(/\/.*$/, '') + '"; \n';
        runtime += '      if(app.name == appName) {\n';
        runtime += '        return p.then(function() {return import(\n';
        runtime += '          /* webpackChunkName: "' + key + '" */\n';
        runtime += '          "' + index + '"\n';
        runtime += '        )}).then(function() {\n';
        runtime += '          appLoadedStates[appName] = "fulfilled";\n';
        runtime += '          appResolves[appName]();\n';
        runtime += '        }).catch(function() {\n';
        runtime += '          appLoadedStates[appName] = "rejected";\n';
        runtime += '          appRejects[appName]();\n';
        runtime += '        });\n';
        runtime += '      }\n\n';
    });

    runtime += "    return p;\n";
    runtime += "    }, Promise.resolve());\n";
    runtime += "    return pms;\n";
    runtime += "  },\n";
    runtime += "  appLoadedStates: appLoadedStates,\n";
    runtime += "  appLoadedPromises: appLoadedPromises\n";
    runtime += "}\n";

    //console.log(runtime);

    return runtime;
};