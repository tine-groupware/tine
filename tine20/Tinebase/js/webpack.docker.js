const { merge } = require('webpack-merge');
const prod = require('./webpack.prod.js');

var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    path: '/out/tine20/Tinebase/js',
    keepInMemory: false,
    removeFullPathAutoPrefix: true,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});

module.exports = async () => {
    const prodConfig = await prod();
    return merge(prodConfig, {
        output: {
            path: '/out/tine20'
        },
        plugins: [
            assetsPluginInstance
        ]
    });
};
