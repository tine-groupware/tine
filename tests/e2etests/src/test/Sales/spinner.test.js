const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 3000});
    await lib.getBrowser('Sales', 'Kund*innen');
});

describe.skip('Mainpage', () => {
    let popupwindow;
    test('open frische fische record', async () => {
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name',{text: "Frische Fische Gmbh & Co. KG"});
        await expectPuppeteer(page).toClick('.x-grid3-cell-inner.x-grid3-col-name',{text:'Frische Fische Gmbh & Co. KG', clickCount: 2});
        popupwindow = await lib.getNewWindow();
        await new Promise(r => setTimeout(r, 5000));
    });

    test('check format of spinner', async () => {
        if ('30' !== await popupwindow.evaluate(() => document.querySelector('input[name=credit_term]').value)) {
            return Promise.reject('Error: credit_term wrong format');
        }

        if ('1,00' !== await popupwindow.evaluate(() => document.querySelector('input[name=currency_trans_rate]').value)) {
            return Promise.reject('Error: currency_trans_rate wrong format');
        }

        if ('15,2' !== await popupwindow.evaluate(() => document.querySelector('input[name=discount]').value)) {
            return Promise.reject('Error: discount wrong format');
        }
    });

    test('change value of spinner', async () => {
        await expectPuppeteer(popupwindow).toFill('input[name=credit_term]', '25,2');
        await expectPuppeteer(popupwindow).toFill('input[name=currency_trans_rate]', '25,2458');
        await expectPuppeteer(popupwindow).toFill('input[name=discount]', '25,8477');
        await expectPuppeteer(popupwindow).toClick('input[name=credit_term]');
        //wait to change value in inputSpinner
        await new Promise(r => setTimeout(r, 500));

        let value;

        value = await popupwindow.evaluate(() => document.querySelector('input[name=credit_term]').value);
        if ('25,2' !== value) {
            return Promise.reject('Error: credit_term wrong format ' + value);
        }

        value = await popupwindow.evaluate(() => document.querySelector('input[name=currency_trans_rate]').value);
        if ('25,25' !== value) {
            return Promise.reject('Error: currency_trans_rate wrong format ' + value);
        }

        value = await popupwindow.evaluate(() => document.querySelector('input[name=discount]').value);
        if ('25,8' !== value) {
            return Promise.reject('Error: discount wrong format '+ value);
        }
        
    })
});

afterAll(async () => {
    browser.close();
});
