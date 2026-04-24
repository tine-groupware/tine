const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Sales');
});

describe('Product', () => {
    test('MainScreen', async () => {
        await new Promise(r => setTimeout(r, 1000));
        await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Produkte'});
        await lib.makeScreenshot(page, {path: 'screenshots/Sales/1_sales_uebersicht.png'});
        await lib.makeScreenshot(
                page, {path: 'screenshots/Sales/2_sales_module.png',
                clip: {x: 0, y: 0, width: 150, height: 300
            }}
        )
    });

    test('open editDialog', async () => {
        await expectPuppeteer(page).toClick('.t-app-sales button', {text: 'Produkt hinzufügen'});
        let newPage = await lib.getNewWindow();
        await new Promise(r => setTimeout(r, 5000));
        await lib.makeScreenshot(newPage, {path: 'screenshots/Sales/3_sales_produkt_neu.png'}); //@todo daten eingeben
        await newPage.close();
    });
});

describe('customer', () => {
    test('MainScreen', async () => {
        await new Promise(r => setTimeout(r, 1000));
        await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Kund*innen'});
        await new Promise(r => setTimeout(r, 1000));
    });
    test('open editDialog', async () => {
        await expectPuppeteer(page).toClick('.t-app-sales button', {text: 'Kund*in hinzufügen'});
        let newPage = await lib.getNewWindow();
        await new Promise(r => setTimeout(r, 5000));
        await lib.makeScreenshot(newPage, {path: 'screenshots/Sales/4_sales_kunden_neu.png'}); //@todo daten eingeben
        await newPage.close();
    });
});

describe('contracts', () => {
    test('MainScreen', async () => {
        await new Promise(r => setTimeout(r, 1000));
        await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Verträge'});
    });
    let newPage;
    test('open editDialog', async () => {
        await new Promise(r => setTimeout(r, 1000));
        await expectPuppeteer(page).toClick('.t-app-sales button', {text: 'Vertrag hinzufügen'});
        newPage = await lib.getNewWindow();
        await new Promise(r => setTimeout(r, 5000));
        await lib.makeScreenshot(newPage, {path: 'screenshots/Sales/5_sales_vertrag_neu.png'}); //@todo daten eingeben
    });
    test('add product', async () => {
        await expectPuppeteer(newPage).toClick('span', {text: 'Produkte'});
        await new Promise(r => setTimeout(r, 1000));
        await lib.makeScreenshot(newPage, {path: 'screenshots/Sales/6_sales_vertrag_neu_produkte.png'});
        await newPage.close();
    });
});

afterAll(async () => {
    browser.close();
});