import lib from '../../lib/browser.js';

// Regression test: "Abbrechen" in an edit dialog must ask before discarding unsaved changes
// (EditDialog.onCancel used to force close the window, as the button was passed as force param).

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung', 'Stundenzettel');
});

describe('Cancel time sheet with unsaved changes', () => {
    const testDescription = 'unsaved description ' + Math.round(Math.random() * 10000000);
    const messageBoxSelector = '.modal.vue-message-box';
    let popupWindow = null;

    const getMessageBoxTitle = (page) => page.evaluate((selector) => {
        const shown = Array.from(document.querySelectorAll(selector))
            .find((el) => getComputedStyle(el).display !== 'none');
        return (shown?.querySelector('.title')?.textContent || '').replace(/\s+/g, ' ').trim();
    }, messageBoxSelector);

    test('Open dialog', async () => {
        popupWindow = await lib.getEditDialog('Stundenzettel hinzufügen', global.page, {selector: '.t-app-timetracker .tine-mainscreen-centerpanel-north .x-btn-text'});
    });

    test('Enter description', async () => {
        await lib.formFillInputField(popupWindow, {selector: '[name="description"]', value: testDescription});
    });

    test('Cancel shows confirm dialog', async () => {
        await lib.clickOnElementWithLabel(popupWindow, 'button', 'Abbrechen');
        await lib.waitForElement(popupWindow, messageBoxSelector, {visible: true});

        expect(await getMessageBoxTitle(popupWindow)).toEqual('Seite verlassen?');
        expect(popupWindow.isClosed()).toBe(false);
    });

    test('Cancel confirm dialog keeps changes', async () => {
        await lib.clickOnElement(popupWindow, `${messageBoxSelector} button.cancel-button`);
        await lib.waitForElement(popupWindow, messageBoxSelector, {hidden: true});

        expect(popupWindow.isClosed()).toBe(false);
        expect(await popupWindow.evaluate(() => document.querySelector('[name="description"]').value)).toEqual(testDescription);
    });

    test('Confirm dialog OK discards changes and closes', async () => {
        await lib.clickOnElementWithLabel(popupWindow, 'button', 'Abbrechen');
        await lib.waitForElement(popupWindow, messageBoxSelector, {visible: true});
        await lib.clickOnElementWaitPageClosed(popupWindow, `${messageBoxSelector} button.ok-button`, 'OK');
    });
});

afterAll(async () => {
    await lib.closeBrowser();
});
