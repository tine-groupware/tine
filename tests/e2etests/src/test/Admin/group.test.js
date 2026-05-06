const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Admin');
    await new Promise(r => setTimeout(r, 1000));
});

describe('gridField account only', () => {
    test('gridField account only', async () => {
        await expectPuppeteer(page).toMatchElement('.x-tree-node span', {text: 'Gruppen'})
        await page.click('.x-tree-node-icon.tinebase-accounttype-group');
        await expectPuppeteer(page).toMatchElement('.x-grid3-hd-account_only');
    })
});

afterAll(async () => {
    browser.close();
});