const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Inventarisierung');
    await new Promise(r => setTimeout(r, 1000));
    await lib.makeScreenshot(page,{path: 'screenshots/Inventarisierung/1_inventar_uebersicht.png'});
});

describe('mainScreen', () => {
    test('import', async () => {
        await new Promise(r => setTimeout(r, 2000));
        let newPage =  await lib.getEditDialog('Einträge importieren');
        await expectPuppeteer(newPage).toMatchElement('.x-btn-text', {text: 'Wählen Sie die Datei mit Ihren Inventargegenstände'});
        await lib.makeScreenshot(newPage,{path:'screenshots/Inventarisierung/5_inventar_import.png'});
        await expectPuppeteer(newPage).toClick('button', {text: 'Abbrechen'});
    })
});

describe('Edit Inventory Item', () => {
    let newPage;
    test('open EditDialog', async () => {
        newPage = await lib.getEditDialog('Inventargegenstand hinzufügen');
        await new Promise(r => setTimeout(r, 5000)); // @todo waitfor selector...
        await lib.makeScreenshot(newPage,{path:'screenshots/Inventarisierung/2_inventar_gegenstand_neu.png'});
        await newPage.click('input[name=status]');
        await new Promise(r => setTimeout(r, 1000));
        await newPage.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-trigger-wrap-focus');
        await lib.makeScreenshot(newPage,{path: 'screenshots/Inventarisierung/3_inventar_gegenstand_status.png'});
    });

    test('accounting', async () => {
        await expectPuppeteer(newPage).toClick('span', {text: 'Buchhaltung'});
        await lib.makeScreenshot(newPage,{path: 'screenshots/Inventarisierung/4_inventar_gegenstand_buchhaltung.png'});
    })
});

afterAll(async () => {
    browser.close();
});
