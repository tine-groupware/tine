let path = require('path')
const { merge } = require('webpack-merge');
const prod = require('./webpack.prod.js');
const webpack = require('webpack');

module.exports = merge(prod, {
    // devtool: 'inline-source-map',
    // entry: null,
    module: {
        rules: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: [
                    /node_modules/,
                    /!(chai-as-promised)/
                ],
                options: {
                    plugins: [
                        '@babel/plugin-transform-runtime',
                        '@babel/plugin-transform-modules-commonjs'
                    ],
                    presets: [
                    ["@babel/env", {/* "debug": true/*, "module": false*/}]

                    ]
                }
            },
            {
                test: /\.js$/,
                use: ['webpack-conditional-loader']
            }
        ]
    },
    resolve: {
        extensions: [".spec.js"],
        modules: [
            path.resolve(__dirname, '../../../tests/js/unit')
        ],
    }
});
