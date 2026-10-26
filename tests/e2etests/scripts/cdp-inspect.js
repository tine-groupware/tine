#!/usr/bin/env node
/**
 * Inspects all pages (incl. popup windows) of a running Chrome via the DevTools protocol.
 * Useful to debug e2e tests running with TEST_BROWSER_URL (see README).
 *
 * usage:
 *   node scripts/cdp-inspect.js list
 *   node scripts/cdp-inspect.js screenshot [<index>|all]   -> screenshots/cdp/<index>.png
 *   node scripts/cdp-inspect.js eval <index> '<js expression>'
 */
import puppeteer from 'puppeteer';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({path: path.resolve(__dirname, '..', '.env')});

const browserURL = process.env.TEST_BROWSER_URL || 'http://127.0.0.1:9222';

(async () => {
    const [cmd = 'list', target = 'all', ...rest] = process.argv.slice(2);
    const browser = await puppeteer.connect({browserURL, defaultViewport: null});
    try {
        const pages = await browser.pages();
        const selected = target === 'all' ? pages : [pages[parseInt(target, 10)]];
        if (selected.some(p => !p)) {
            throw new Error(`no page with index ${target}, see "list"`);
        }

        switch (cmd) {
            case 'list':
                for (const [i, p] of pages.entries()) {
                    console.log(`${i}\t${await p.title()}\t${p.url()}`);
                }
                break;
            case 'screenshot': {
                const dir = path.resolve(__dirname, '..', 'screenshots', 'cdp');
                fs.mkdirSync(dir, {recursive: true});
                for (const p of selected) {
                    const file = path.join(dir, `${pages.indexOf(p)}.png`);
                    await p.screenshot({path: file});
                    console.log(file);
                }
                break;
            }
            case 'eval': {
                const result = await selected[0].evaluate(rest.join(' '));
                console.log(typeof result === 'string' ? result : JSON.stringify(result, null, 2));
                break;
            }
            default:
                throw new Error(`unknown command "${cmd}"`);
        }
    } finally {
        browser.disconnect();
    }
})().catch(e => {
    console.error(e.message);
    process.exit(1);
});
