import lib from '../../lib/browser.js';

// Regression test: "Abbrechen" in an edit dialog must ask before discarding unsaved changes
// (EditDialog.onCancel used to force close the window, as the button was passed as force param).
// On cancel the user can skip this confirmation ("do not show again"), the state is reset after the test.

const leaveStateId = 'tinebase-editdialog-cancel-leavesite';
const clearLeaveState = () => global.page.evaluate((stateId) => Ext.state.Manager.clear(stateId), leaveStateId);

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung', 'Stundenzettel');
    await clearLeaveState();
});

describe('Cancel time sheet with unsaved changes', () => {
    const testDescription = 'unsaved description ' + Math.round(Math.random() * 10000000);
    const messageBoxSelector = '.modal.vue-message-box';
    const messageBoxTitle = 'Seite verlassen?';
    let popupWindow = null;

    test('Open dialog', async () => {
        popupWindow = await lib.getEditDialog('Stundenzettel hinzufügen', global.page, {selector: '.t-app-timetracker .tine-mainscreen-centerpanel-north .x-btn-text'});
    });

    test('Enter description', async () => {
        await lib.formFillInputField(popupWindow, {selector: '[name="description"]', value: testDescription});
    });

    test('Cancel shows confirm dialog, cancel keeps changes', async () => {
        await lib.clickOnElementWithLabel(popupWindow, 'button', 'Abbrechen');
        await lib.waitForElement(popupWindow, `${messageBoxSelector} .modal-footer input[type="checkbox"]`, {visible: true});
        await lib.answerMessageBox(popupWindow, 'cancel', {title: messageBoxTitle});

        expect(popupWindow.isClosed()).toBe(false);
        expect(await popupWindow.evaluate(() => document.querySelector('[name="description"]').value)).toEqual(testDescription);
    });

    test('Confirm dialog OK with "do not show again" discards changes and closes', async () => {
        await lib.clickOnElementWithLabel(popupWindow, 'button', 'Abbrechen');
        await lib.waitForElement(popupWindow, messageBoxSelector, {visible: true});
        // NOTE: the dialog closes with the message box, so answerMessageBox can't wait for the message box to be hidden
        await lib.clickOnElement(popupWindow, `${messageBoxSelector} .modal-footer input[type="checkbox"]`);
        await popupWindow.waitForFunction((selector) => document.querySelector(`${selector} .modal-footer input[type="checkbox"]`).checked, {}, messageBoxSelector);
        await lib.clickOnElementWaitPageClosed(popupWindow, `${messageBoxSelector} button.ok-button`, 'OK');
    });

    test('Cancel with unsaved changes closes without confirm dialog', async () => {
        popupWindow = await lib.getEditDialog('Stundenzettel hinzufügen', global.page, {selector: '.t-app-timetracker .tine-mainscreen-centerpanel-north .x-btn-text'});
        await lib.formFillInputField(popupWindow, {selector: '[name="description"]', value: testDescription});
        await lib.clickOnElementWaitPageClosed(popupWindow, 'button', 'Abbrechen');
    });
});

afterAll(async () => {
    await clearLeaveState();
    await lib.closeBrowser();
});
