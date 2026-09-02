const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

// TODO: Create a dummy time account in the test setup, use it for testing instead of relying on existing data, and delete it again.

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
        await lib.formFillComboField(popupWindow, {selector: 'input[name="timeaccount_id"]', inputValue: 'test', itemValue: '1 - Test Timeaccount 1'});
    });

    test('Enter duration and end time', async() => {
        // Try a lot of different formats.
        // NOTE: This relates to a "spinner" input field, which does annoying ExtJS validation, requiring us to work around it (more delay between typing, see formFillInputField() method).
        for (const val of ['03:30', '3:30', '3.5', '3,5']) {
            await lib.formFillInputField(popupWindow, {selector: 'input[name="duration"]', value: val, expectedValue: '03:30', typingDelay: 100});
        }

        // Try a lot of different formats as well.
        for (const val of ['08:30', '8:30', '830']) {
            await lib.formFillInputField(popupWindow, {selector: 'input[name="start_time"]', value: val, expectedValue: '08:30'});
        }

        // Check if the current username is correct.
        const currentUser = await lib.getCurrentUser(popupWindow);
        expect(await popupWindow.evaluate(() => document.querySelector('input[name=account_id]').value)).toEqual(currentUser.accountDisplayName);
    });

    test('Enter description', async () => {
        await lib.formFillInputField(popupWindow, {selector: '[name="description"]', value: testDescription});
    });

    test('Confirm', async() => {
        await expectPuppeteer(popupWindow).toClick('button', {text: 'Ok'});
    });

    test('Check values in the grid', async() => {
        // Refresh grid and compare the values.
        await lib.formRefreshGrid(global.page, '.t-app-timetracker');
        await expectPuppeteer(global.page).toMatchElement('div.x-grid3-col-timeaccount_id', {text: '1 - Test Timeaccount 1', visible: true});
        await expectPuppeteer(global.page).toMatchElement('div.x-grid3-col-description', {text: testDescription, visible: true});
        await expectPuppeteer(global.page).toMatchElement('div.x-grid3-col-duration span.duration-renderer-medium', {text: '3 Stunden, 30 Minuten'});
        await expectPuppeteer(global.page).toMatchElement('div.x-grid3-col-duration span.duration-renderer-small', {text: '3:30'});
        await expectPuppeteer(global.page).toMatchElement('div.x-grid3-col-accounting_time span.duration-renderer-medium', {text: '3 Stunden, 30 Minuten'});
    });

    test('Delete and confirm', async() => {
        // Click on entry and press Delete key.
        await global.page.waitForSelector('div.x-grid3-col-description', {visible: true});
        await expectPuppeteer(global.page).toClick('div.x-grid3-col-description', {text: testDescription});
        await global.page.waitForSelector('.x-grid3-row-selected', { visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE') });
        await global.page.keyboard.press('Delete');

        // Wait for modal confirmation dialog to appear, click on "Ja" and wait until the dialog disappears.
        await global.page.waitForSelector('button.yes-button', {visible: true});
        await expectPuppeteer(global.page).toClick('button.yes-button', {text: 'Ja'});
        await expectPuppeteer(global.page).not.toMatchElement('div.modal.vue-message-box.show');

        // Refresh grid and check for absence of the entry.
        await lib.formRefreshGrid(global.page, '.t-app-timetracker');
        await expectPuppeteer(global.page).not.toMatchElement('div.x-grid3-col-description', {text: testDescription});
    });
});

afterAll(async () => {
    global.browser.close();
});
