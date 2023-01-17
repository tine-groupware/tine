const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const TerserPlugin = require('terser-webpack-plugin');
const webpack = require('webpack');

module.exports = merge(common, {
    devtool: 'eval',
    plugins: [
        new webpack.DefinePlugin({
            BUILD_TYPE: "'DEVELOPMENT'"
        })
    ],
    mode: 'development',
    devServer: {
        hot: false,
        liveReload: false,
        host: '0.0.0.0',
        port: 10443,

        allowedHosts: 'all',
        headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
            "Access-Control-Allow-Headers": "X-Requested-With, content-type, Authorization"
        },
        proxy: [
            {
                context: ['**', '!/webpack-dev-server*/**', '!**.json', '!/ws'],
                target: 'http://localhost/',
                secure: false
            }
        ],
        client: {
            overlay: true,
        },
        // onBeforeSetupMiddleware: function(app, server) {
        //     app.use(function(req, res, next) {
        //         // check for langfile chunk requests
        //         // build on demand
        //         // extract-text
        //         next();
        //     });
        // }
    }
});
