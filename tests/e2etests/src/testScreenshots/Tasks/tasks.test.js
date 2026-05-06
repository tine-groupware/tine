const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Aufgaben');
    await lib.makeScreenshot(page, {path: 'screenshots/Aufgaben/1_aufgaben_uebersicht.png'});
});

describe('Edit Contact', () => {
    let popupWindow;
    test('open EditDialog', async () => {

        popupWindow = await lib.getEditDialog('Aufgabe hinzufügen');
        await popupWindow.type('input[name=summary]', 'Bewerbungsunterlagen sondieren');
        await lib.makeScreenshot(popupWindow,{path: 'screenshots/Aufgaben/2_aufgaben_neue_aufgabe.png'});
    });

    test.skip('notification', async () => {
        await expectPuppeteer(popupWindow).toClick('span', {text: 'Alarm', clickCount: 1});
        await popupWindow.click('.new-row .x-form-trigger.x-form-arrow-trigger');
        await new Promise(r => setTimeout(r, 500));
        await lib.makeScreenshot(popupWindow,{path: 'screenshots/Aufgaben/3_aufgaben_alarm.png'});
    });
});

afterAll(async () => {
    browser.close();
});
