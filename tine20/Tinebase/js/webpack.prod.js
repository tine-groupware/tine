global.mode = 'production';
const { merge } = require('webpack-merge');
const webpack = require('webpack');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin')
const common = require('./webpack.common.js');
const BrotliPlugin = require('brotli-webpack-plugin');

module.exports = merge(common, {
    devtool: 'source-map',
    mode: 'production',
    optimization:{
        minimizer: [new TerserPlugin({
            extractComments: 'all',
            terserOptions: {},
        })],
    },
    plugins: [
        new webpack.DefinePlugin({
            BUILD_TYPE: "'RELEASE'"
        }),
        new UnminifiedWebpackPlugin({
            postfix : 'debug',
            replace: [[/(Tine\.clientVersion\.buildType\s*=\s*)'RELEASE'/, "$1'DEBUG'"]],
            exclude: /Tinebase\/css\/build\/.*/
        }),
        new BrotliPlugin({})
    ],
});
