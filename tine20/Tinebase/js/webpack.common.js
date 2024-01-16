var fs = require('fs');
var _ = require('lodash');
var path = require('path');
var webpack = require('webpack');

// @TODO: replace by https://github.com/shellscape/webpack-manifest-plugin ?
var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    // path: 'Tinebase/js',
    // fullPath: false,
    removeFullPathAutoPrefix: true,
    keepInMemory: global.mode !== 'production',
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});
var {VueLoaderPlugin} = require('vue-loader');
var ChunkNamePlugin = require('./webpack.ChunkNamePlugin');
var ESLintPlugin = require('eslint-webpack-plugin');

var eslintPluginInstance = new ESLintPlugin({
    formatter: require('eslint-friendly-formatter'),
    extensions: ['mjs', 'es6.js', 'vue'],
    quiet: true,
    overrideConfig: {
        extends: ["standard", "plugin:vue/essential"],
        plugins: ["notice", "vue"],
        parserOptions: {
            parser: "@babel/eslint-parser",
            requireConfigFile: false
        }
    },
})

// use https://github.com/Richienb/node-polyfill-webpack-plugin ?
// the plugin just does the following.
// it's better to do this ourselves, instead of relying on the plugin
// to prevent possible lib conflicts later on.
var providePlugin = new webpack.ProvidePlugin({
    Buffer: [require.resolve('buffer/'), 'Buffer'],
    process: require.resolve('process/browser')
})

var definePlugin = new webpack.DefinePlugin({
    BUILD_DATE:     JSON.stringify(process.env.BUILD_DATE),
    BUILD_REVISION: JSON.stringify(process.env.BUILD_REVISION),
    CODE_NAME:      JSON.stringify(process.env.CODE_NAME),
    PACKAGE_STRING: JSON.stringify(process.env.PACKAGE_STRING),
    RELEASE_TIME: JSON.stringify(process.env.RELEASE_TIME),
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: true
});

var baseDir = path.resolve(__dirname, '../../'),
    entryPoints = {};

// find all entry points
fs.readdirSync(baseDir).forEach(function (baseName) {
    // if (baseName !== 'Filemanager') return;
    try {
        // try npm package.json
        var pkgDef = JSON.parse(fs.readFileSync(baseDir + '/' + baseName + '/js/package.json').toString());

        _.each(_.get(pkgDef, 'tine20.entryPoints', []), function (entryPoint) {
            entryPoints[baseName + '/js/' + entryPoint] = baseDir + '/' + baseName + '/js/' + entryPoint;
        });

    } catch (e) {
        // no package.json - no entry defined
    }
});

module.exports = {
    target: ['web', 'es6'],
    entry: entryPoints,
    optimization: {
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
    externalsType: "window",
    output: {
        path: baseDir + '/',
        publicPath: 'auto',
        filename: '[name]-[fullhash]-FAT.js',
        chunkFilename: "[name]-[chunkhash]-FAT.js",
        libraryTarget: "umd",
        clean: {
            keep(asset) {
                return !asset.includes('-FAT.') || asset.includes('webpack-assets-FAT.json');
            },
        }
    },
    plugins: [
        definePlugin,
        assetsPluginInstance,
        new VueLoaderPlugin(),
        new ChunkNamePlugin(),
        providePlugin,
        eslintPluginInstance
    ],
    module: {
        rules: [
            // {
            //     test: /\.(mjs|es6\.js|vue)$/,
            //     loader: 'eslint-loader',
            //     enforce: "pre",
            //     exclude: /node_modules/,
            //     options: {
            //         formatter: require('eslint-friendly-formatter')
            //     }
            // },
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
                        ["@babel/plugin-proposal-decorators", { "decoratorsBeforeExport": false }],
                    ],
                    presets: [
                        "@babel/preset-env"
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
                        ["@babel/plugin-proposal-decorators", { "legacy": true }],
                    ],
                    presets: [
                        "@babel/preset-env"
                    ]
                }
            },
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                // exclude: /node_modules/,
            },
            // {
            //     test: /\.js$/,
            //     include: [
            //         require.resolve("bootstrap-vue"), // white-list bootstrap-vue
            //     ],
            //     loader: "babel-loader"
            // },

            // use script loader for old library classes as some of them the need to be included in window context
            { test: /\.js$/, include: [baseDir + '/library'], exclude: [baseDir + '/library/ExtJS'], enforce: "pre", use: [{ loader: "script-loader" }] },
            { test: /\.jsb2$/, use: [{ loader: "./jsb2-loader" }] },
            { test: /\.css$/, use: [{ loader: "style-loader" }, { loader: "css-loader" }] },
            { test: /\.scss$/, use: ['style-loader','css-loader', 'sass-loader'] },
            { test: /\.less$/, use: [{ loader: "style-loader" }, { loader: "css-loader" }, { loader: "less-loader", options: { lessOptions: { noIeCompat: true, } } }] },
            {
                test: /\.(woff2?|eot|ttf|otf|png|gif|svg)(\?.*)?$/,
                type: 'asset/inline'
            },
        ]
    },
    resolveLoader: {
        modules: [path.resolve(__dirname, "node_modules")]
    },
    resolve: {
        extensions: [".tsx", ".ts", ".js", ".es6.js"],
        // add browserify which is used by some libs (e.g. director)
        mainFields: ["browser", "browserify", "module", "main"],
        // we need an absolut path here so that apps can resolve modules too
        modules: [
            path.resolve(__dirname, "../.."),
            __dirname,
            path.resolve(__dirname, "node_modules")
        ],
        fallback: {
            'crypto': require.resolve("crypto-browserify"),
            'path': require.resolve("path-browserify"),
            'buffer': require.resolve('buffer'),
            'util': require.resolve("util/"),
            'process': require.resolve('process/browser'),
            'stream': require.resolve("stream-browserify"),
        },
        alias: {
            // convinence alias
            "tine-vue$": path.resolve(__dirname, "node_modules/vue/dist/vue.runtime.esm-bundler.js"),
        }
    }
};
