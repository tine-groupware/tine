import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Admin');
    await new Promise(r => setTimeout(r, 1000));
});

describe('gridField account only', () => {
    test('gridField account only', async () => {
        await expectPuppeteer(page).toMatchElement('.x-tree-node span', {text: 'Gruppen'})
        await global.page.click('.x-tree-node-icon.tinebase-accounttype-group');
        await expectPuppeteer(page).toMatchElement('.x-grid3-hd-account_only');
    })
});

afterAll(async () => {
    global.browser.close();
});