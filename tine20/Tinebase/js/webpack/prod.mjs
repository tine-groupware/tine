global.mode = 'production';
import { merge } from 'webpack-merge';
import webpack from 'webpack';
import UnminifiedWebpackPlugin from 'unminified-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import common from './common.mjs';
import BrotliPlugin from 'brotli-webpack-plugin';

export default async () => {
    const commonConfig = await common();

    return merge(commonConfig, {
        devtool: 'source-map',
        mode: 'production',
        optimization: {
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
                postfix: 'debug',
                replace: [[/(Tine\.clientVersion\.buildType\s*=\s*)'RELEASE'/, "$1'DEBUG'"]],
                exclude: /Tinebase\/styles\/build\/.*/
            }),
            new BrotliPlugin({})
        ],
    });
};
