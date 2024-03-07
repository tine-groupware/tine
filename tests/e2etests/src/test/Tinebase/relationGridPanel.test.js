const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
});

describe('Mainpage', () => {

    test('add filemanager relation', async () => {
        let popupWindow = await lib.getEditDialog('Kontakt hinzufügen');

        // add link to personal folder
        await expect(popupWindow).toClick('span', {text: new RegExp("Verknüpfungen.*")});
        await popupWindow.waitForSelector('.x-grid3-hd.x-grid3-cell.x-grid3-td-remark');
        let arrows = await popupWindow.$$('.x-panel.x-wdgt-pickergrid.x-grid-panel .x-form-trigger.x-form-arrow-trigger');
        await arrows[0].click();
        await popupWindow.waitForTimeout(2000);
        await expect(popupWindow).toClick('.x-combo-list-item ', {text: 'Dateimanager'});
        await popupWindow.waitForTimeout(2000);
        await popupWindow.click('.x-form-trigger.undefined');
        await popupWindow.waitForSelector('.x-panel.x-panel-noborder.x-grid-panel');
        await popupWindow.waitForTimeout(3000);
        await popupWindow.click('.x-window-bwrap .x-grid3-cell-inner.x-grid3-col-name',{clickCount:2});
        await popupWindow.waitForTimeout(3000);
        await expect(popupWindow).toClick('.x-window.x-window-plain.x-resizable-pinned button',{text: 'Ok'});
        await popupWindow.waitForTimeout(3000);

        // show in filemanager
        await popupWindow.click('.x-grid3-row.x-grid3-row-first.x-grid3-row-last', {button: 'right'});
        await popupWindow.waitForSelector('.x-menu-list', {visible: true});
        await popupWindow.waitForSelector('span' , {text: 'Im Dateimanager anzeigen', visible: true});
        await expect(popupWindow).toClick('span' , {text: 'Im Dateimanager anzeigen', visible: true});
        await page.waitForSelector('.x-tab-with-icon.x-tab-strip-active .ApplicationIconCls.FilemanagerIconCls');

    });
});

afterAll(async () => {
    browser.close();
});
