import { merge } from 'webpack-merge';
import prod from './prod.mjs';

export default async () => {
    const prodConfig = await prod();
    return merge(prodConfig, {
        output: {
            path: '/out/tine20'
        },
    });
};
