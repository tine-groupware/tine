var fs = require('fs');
var _ = require('lodash');
var path = require('path');
var webpack = require('webpack');
var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    // path: 'Tinebase/js',
    // fullPath: false,
    keepInMemory: true,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});
var VueLoaderPlugin = require('vue-loader/lib/plugin');
var ChunkNamePlugin = require('./webpack.ChunkNamePlugin');

var definePlugin = new webpack.DefinePlugin({
    BUILD_DATE:     JSON.stringify(process.env.BUILD_DATE),
    BUILD_REVISION: JSON.stringify(process.env.BUILD_REVISION),
    CODE_NAME:      JSON.stringify(process.env.CODE_NAME),
    PACKAGE_STRING: JSON.stringify(process.env.PACKAGE_STRING),
    RELEASE_TIME:   JSON.stringify(process.env.RELEASE_TIME)
});

var baseDir  = path.resolve(__dirname , '../../'),
    entryPoints = {};

// find all entry points
fs.readdirSync(baseDir).forEach(function(baseName) {
    // if (baseName !== 'Filemanager') return;
    try {
        // try npm package.json
        var pkgDef = JSON.parse(fs.readFileSync(baseDir + '/' + baseName + '/js/package.json').toString());

        _.each(_.get(pkgDef, 'tine20.entryPoints', []), function(entryPoint) {
            entryPoints[baseName + '/js/' + entryPoint] = baseDir + '/' + baseName + '/js/' + entryPoint;
        });

    } catch (e) {
        // no package.json - no entry defined
    }
});

module.exports = {
    entry: entryPoints,
    optimization:{
        /**
         * NOTE: there are some problems with auto/common chunk splitting atm
         *    i) common chunks might be placed in an application dir and the application might not be installed
         *       thus the chunk would be missing in the installation. It might be an option to place all chunks
         *       inside Tinebase (e.g. via automaticNamePrefix: 'Tinebase/js' but then also app specific chunks (also
         *       of custom apps from buildtime) would be part of tinebase.
         *   ii) our module name is <app>/js/<app>-FAT.js. This leads to a automatic <app>/js prefix in the gernerated
         *       chunk names and thus to a deep awkward directory structure
         *  iii) using name function config should help. but it's not clear how to use it to split/merge the chunks
         *       this needs more investigations an might not even be possible
         *
         *  => we disable common chunks splitting for  now
         */
        splitChunks: false
    },
    externals: {
        fs: "fs",
    },
    output: {
        path: baseDir + '/',
        // avoid public path, see #13430.
        // publicPath: '/',
        filename: '[name]-[hash]-FAT.js',
        chunkFilename: "[name]-[chunkhash]-FAT.js",
        libraryTarget: "umd"
    },
    plugins: [
        definePlugin,
        assetsPluginInstance,
        new VueLoaderPlugin(),
        new ChunkNamePlugin()
    ],
    module: {
        rules: [
            {
                test: /\.(mjs|es6\.js|vue)$/,
                loader: 'eslint-loader',
                enforce: "pre",
                exclude: /node_modules/,
                options: {
                    formatter: require('eslint-friendly-formatter')
                }
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.mjs$/,
                loader: 'babel-loader',
                exclude: [
                    /node_modules/,
                ],
                options: {
                    plugins: [
                        "@babel/plugin-transform-runtime",
                        "@babel/plugin-syntax-dynamic-import",
                        ["@babel/plugin-proposal-decorators", { "decoratorsBeforeExport": false }],
                        "@babel/plugin-proposal-class-properties"
                    ],
                    presets: [
                        ["@babel/preset-env", { "modules": true }]
                    ]
                }
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: [
                    /node_modules/,
                ],
                options: {
                    plugins: [
                        "@babel/plugin-transform-runtime",
                        "@babel/plugin-syntax-dynamic-import",
                        ["@babel/plugin-proposal-decorators", { "legacy": true }],
                        "@babel/plugin-proposal-class-properties"
                    ],
                    presets: [
                        ["@babel/preset-env"/*, { "modules": false }*/]
                    ]
                }
            },
            {
                test: /\.js$/,
                include: [
                    require.resolve("bootstrap-vue"), // white-list bootstrap-vue
                ],
                loader: "babel-loader"
            },

            // use script loader for old library classes as some of them the need to be included in window context
            {test: /\.js$/, include: [baseDir + '/library'], exclude: [baseDir + '/library/ExtJS'],  enforce: "pre", use: [{loader: "script-loader"}]},
            {test: /\.jsb2$/, use: [{loader: "./jsb2-loader"}]},
            {test: /\.css$/, use: [{loader: "style-loader"}, {loader: "css-loader"}]},
            {test: /\.scss$/, use: ['vue-style-loader','css-loader','sass-loader']},
            {test: /\.less$/, use: [{loader: "style-loader"}, {loader: "css-loader"}, {loader: "less-loader", options: {noIeCompat: true,}}]},
            {test: /\.png/, use: [{loader: "url-loader", options: {limit: 100000}}]},
            {test: /\.gif/, use: [{loader: "url-loader", options: {limit: 100000}}]},
            {test: /\.svg/, use: [{loader: "svg-url-loader"},{loader: "./svg-fix-size-loader"}]},
            {
                test: /\.(woff2?|eot|ttf|otf)(\?.*)?$/,
                use: [{loader: "url-loader", options: {limit: 100000}}]
            },
        ]
    },
    resolveLoader: {
        modules: [path.resolve(__dirname, "node_modules")]
    },
    resolve: {
        extensions: [".js", ".es6.js"],
        // add browserify which is used by some libs (e.g. director)
        mainFields: ["browser", "browserify", "module", "main"],
        // we need an absolut path here so that apps can resolve modules too
        modules: [
            path.resolve(__dirname, "../.."),
            __dirname,
            path.resolve(__dirname, "node_modules")
        ]
    }
};
