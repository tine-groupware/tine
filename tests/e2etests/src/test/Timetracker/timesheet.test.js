const expectPuppeteer = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung', 'Stundenzettel');
});

describe('Create and delete time sheet', () => {
    const testDescription = 'test description ' + Math.round(Math.random() * 10000000);
    let popupWindow = null;

    test('Open dialog', async () => {
        popupWindow = await lib.getEditDialog('Stundenzettel hinzufügen');
        await expectPuppeteer(popupWindow).toMatchElement('span.x-tab-strip-text', {text: 'Stundenzettel'});
    });

    test('Select time account', async() => {
        await popupWindow.waitForSelector('[name="timeaccount_id"]');
        await expectPuppeteer(popupWindow).toFill('[name="timeaccount_id"]', 'test');
        await popupWindow.waitForSelector('.x-combo-list-item');
        await expectPuppeteer(popupWindow).toClick('.x-combo-list-item', {text: '1 - Test Timeaccount 1'});
    });

    test('Enter start and end time', async() => {
        const currentUser = await lib.getCurrentUser(popupWindow);

        await popupWindow.waitForSelector('input[name="duration"]');
        await expectPuppeteer(popupWindow).toFill('input[name="duration"]', '03:30');
        await popupWindow.waitForTimeout(500);

        await popupWindow.waitForSelector('input[name="start_time"]');
        await expectPuppeteer(popupWindow).toFill('input[name="start_time"]', '08:00');
        await popupWindow.waitForTimeout(500);

        expect(await popupWindow.evaluate(() => document.querySelector('input[name=account_id]').value)).toEqual(currentUser.accountDisplayName);
    });

    test('Enter description', async () => {
        await popupWindow.waitForSelector('[name="description"]');
        await expectPuppeteer(popupWindow).toClick('[name="description"]');
        await expectPuppeteer(popupWindow).toFill('[name=description]', testDescription);
    });

    test('Confirm', async() => {
        await expectPuppeteer(popupWindow).toClick('button', {text: 'Ok'});
    });

    // FIXME make it work
    test('Check values in the grid', async() => {
        await page.click('.t-app-timetracker .x-btn-image.x-tbar-loading');
        await page.waitForSelector('.t-app-timetracker .x-btn-image.x-tbar-loading');
        await page.waitForTimeout(1000);
        await expectPuppeteer(page).toMatchElement('div.x-grid3-col-timeaccount_id', {text: '1 - Test Timeaccount 1'});
        await expectPuppeteer(page).toMatchElement('div.x-grid3-col-description', {text: testDescription});
        //await expectPuppeteer(page).toMatchElement('div.x-grid3-col-duration', {text: '3 Stunden, 30 Minuten'});
    });

    // FIXME make it work
    test('Delete and confirm', async() => {
        await expectPuppeteer(page).toClick('div.x-grid3-col-description', {text: testDescription});
        await page.keyboard.press('Delete');
        await page.waitForSelector('.x-btn-icon-small-left');
        await expectPuppeteer(page).toClick('button', {text: 'Ja'});
        await page.click('.t-app-timetracker .x-btn-image.x-tbar-loading');
        await page.waitForSelector('.t-app-timetracker .x-btn-image.x-tbar-loading');
        await page.waitForTimeout(1000);
        await expectPuppeteer(page).not.toMatchElement('div.x-grid3-col-description', {text: testDescription});
    });
});

afterAll(async () => {
    browser.close();
});
