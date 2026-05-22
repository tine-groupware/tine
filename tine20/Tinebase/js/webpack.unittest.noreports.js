let path = require('path')
const { merge } = require('webpack-merge');
const prod = require('./webpack.prod.js');

module.exports = async () => {
    const prodConfig = await prod();
    return merge(prodConfig, {
        // entry: null,
        devtool: 'inline-source-map',
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
                            ["@babel/env"/*, { "modules": false }*/]
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
};
