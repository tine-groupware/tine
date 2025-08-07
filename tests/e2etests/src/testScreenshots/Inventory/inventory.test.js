const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Inventarisierung');
    await page.waitForTimeout(1000);
    await lib.makeScreenshot(page,'screenshots/Inventarisierung/1_inventar_uebersicht.png');
});

describe('mainScreen', () => {
    test('import', async () => {
        let newPage =  lib.getNewWindow();
        try {
            await page.waitForTimeout(2000);
            await expect(page).toClick('.t-app-inventory button', {text: 'Einträge importieren', visible: true});
        } catch (e) {
            await page.waitForTimeout(1000);
            await expect(page).toClick('.x-btn-image.x-toolbar-more-icon', {visible: true});
            await page.click('.x-menu-item-icon action_import', {text: 'Einträge importieren', visible: true});
        }
        newPage = await newPage;
        await newPage.waitForXPath('//button');
        await lib.makeScreenshot(newPage,'screenshots/Inventarisierung/5_inventar_import.png');
        await newPage.keyboard.press('Escape')
        await expect(newPage).toClick('button', {text: 'Abbrechen'});
    })
});

describe('Edit Inventory Item', () => {
    let newPage;
    test('open EditDialog', async () => {
        newPage = await lib.getEditDialog('Inventargegenstand hinzufügen');
        await newPage.waitForTimeout(5000); // @todo waitfor selector...
        await lib.makeScreenshot(newPage,'screenshots/Inventarisierung/2_inventar_gegenstand_neu.png');
        await newPage.click('input[name=status]');
        await newPage.waitForTimeout(1000);
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus');
        await lib.makeScreenshot(newPage,'screenshots/Inventarisierung/3_inventar_gegenstand_status.png', true);
    });

    test('accounting', async () => {
        await expect(newPage).toClick('span', {text: 'Buchhaltung'});
        await lib.makeScreenshot(newPage,'screenshots/Inventarisierung/4_inventar_gegenstand_buchhaltung.png');
    })
});

afterAll(async () => {
    browser.close();
});
