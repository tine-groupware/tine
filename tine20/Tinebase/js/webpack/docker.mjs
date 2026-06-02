import { merge } from 'webpack-merge';
import prod from './prod.mjs';


const AssetsPlugin = require('assets-webpack-plugin');
const assetsPluginInstance = new AssetsPlugin({
    path: '/out/tine20/Tinebase/js',
    keepInMemory: false,
    removeFullPathAutoPrefix: true,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});

export default async () => {
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
