const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('E-Mail');

});

describe('message', () => {
    test('update folder cache ', async () => {
        const currenUser = await lib.getCurrentUser(page);
        await expectPuppeteer(page).not.toMatchElement('.x-tree-node-el.x-unselectable.felamimail-node-account.x-tree-node-expanded.x-tree-node-loading');
        await expectPuppeteer(page).toMatchElement('span', {text: currenUser.accountEmailAddress});
        await expectPuppeteer(page).toClick('span', {text: currenUser.accountEmailAddress, button: 'right'});
        await page.waitForSelector('.x-menu-list', {visible:true});
        await page.waitForSelector('.x-menu-item-icon.action_update_cache', {visible: true});
        await page.click('.x-menu-item-icon.action_update_cache');
        try {
            await page.waitForSelector('.x-tree-node-el.x-unselectable.felamimail-node-account.x-tree-node-expanded.x-tree-node-loading');
        } catch {}
        await expectPuppeteer(page).not.toMatchElement('.x-tree-node-el.x-unselectable.felamimail-node-account.x-tree-node-expanded.x-tree-node-loading');
        await page.waitForSelector('a span',{text: "Posteingang"});
        await expectPuppeteer(page).toClick('a span',{text: "Posteingang"});
    });

    let popupWindow;
    test('compose message with uploaded attachment', async () => {
        popupWindow = await lib.getEditDialog('Verfassen');
        let currentUser = await lib.getCurrentUser(popupWindow);
        // add recipient
        await new Promise(r => setTimeout(r, 2000));
        let inputFields = await popupWindow.$$('input');
        await inputFields[2].type(currentUser.accountEmailAddress);
        await new Promise(r => setTimeout(r, 2000)); //musst wait for input!
        await popupWindow.waitForSelector('.search-item.x-combo-selected');
        await popupWindow.click('.search-item.x-combo-selected');
        await new Promise(r => setTimeout(r, 500)); //wait for new mail line!
        await popupWindow.click('input[name=subject]');
        await new Promise(r => setTimeout(r, 1000)); //musst wait for input!
        await expectPuppeteer(popupWindow).toFill('input[name=subject]', 'message with attachment');
        await new Promise(r => setTimeout(r, 1000));

        const fileToUpload = 'src/test/Felamimail/attachment.txt';
        let filePickerWindow = lib.getNewWindow();
        await expectPuppeteer(popupWindow).toClick('.x-btn-text', {text: 'Datei hinzufügen'});
        filePickerWindow = await filePickerWindow;
        await expectPuppeteer(filePickerWindow).toMatchElement('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
        await expectPuppeteer(filePickerWindow).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, count: 2});
        await new Promise(r => setTimeout(r, 2000)); //musst wait!
        await filePickerWindow.waitForSelector('input[type=file]');
        let inputUploadHandle = await filePickerWindow.$('input[type=file]');
        await inputUploadHandle.uploadFile(fileToUpload);
        await new Promise(r => setTimeout(r, 5000));
        await expectPuppeteer(filePickerWindow).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});
        await expectPuppeteer(filePickerWindow).toClick('button', {text: 'Ok'});
        
        await new Promise(r => setTimeout(r, 2000));
        await expectPuppeteer(popupWindow).toMatchElement('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});
        await new Promise(r => setTimeout(r, 2000)); //musst wait for upload complete!
        
        // send message
        await expectPuppeteer(popupWindow).toClick('button', {text: 'Senden'});
    });

    // test('compose message with filemanager attachment', async () => {
    //     await expectPuppeteer(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
    //     await expectPuppeteer(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
    //    
    //     // @TODO
    // });
    
    let newMail;
    test('fetch messages', async () => {
        await new Promise(r => setTimeout(r, 2000)); //wait to close editDialog

        for(let i = 0; i < 10; i++) {
            await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
            await new Promise(r => setTimeout(r, 500));
            try{
                await expectPuppeteer(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment', timeout: 2000});
                break;
            } catch(e){
            }
        }

        await new Promise(r => setTimeout(r, 500));
        newMail = await expectPuppeteer(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment'});
        await new Promise(r => setTimeout(r, 500));
        await newMail.click();
    });

    test('details panel', async () => {
        await page.waitForSelector('.preview-panel-felamimail');
    });

    test('contextMenu', async () => {
        await newMail.click({button: 'right'});
        await lib.makeScreenshot(page,{path: 'screenshots/EMail/17_email_kontextmenu_email.png'});
        await page.keyboard.press('Escape')
    })

    let attachment;
    test.skip('download attachments', async () => {
        let popupWindow = lib.getNewWindow();
        newMail.click({clickCount: 2});
        popupWindow = await popupWindow
        //await popupWindow.waitForSelector('.ext-el-mask');
        await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachment = await popupWindow.$$('.tinebase-download-link');
        await attachment[1].hover();
        await attachment[1].click('tinebase-download-link-wait');

        let file = await lib.download(popupWindow, '.x-menu-item-text', {text:'Herunterladen'});

        if(!file.includes('attachment')) {
            throw new Error('download of attachments failed!');
        }
    });

    test.skip('file attachment', async () => {
        await popupWindow.waitForSelector('.tinebase-download-link');
        attachment = await popupWindow.$$('.tinebase-download-link');
        await attachment[1].hover();
        await attachment[1].click('tinebase-download-link-wait');
        await expectPuppeteer(popupWindow).toClick('.x-menu-item-text',
            {text: new RegExp('Datei.*'), visible: true});
        await popupWindow.waitForSelector('.x-grid3-row.x-grid3-row-first');
        await popupWindow.click('.x-grid3-row.x-grid3-row-first');
        await expectPuppeteer(popupWindow).toClick('button', {text: 'Ok'});
        await popupWindow.close();
    });

    test.skip('attachment file in filemanager', async () => {
        await expectPuppeteer(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expectPuppeteer(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
        await expectPuppeteer(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 2});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'attachment.txt'});
        await expectPuppeteer(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expectPuppeteer(page).toClick('.x-menu-item-text', {text: 'Felamimail'});
        await page.waitForSelector('a span',{text: "Posteingang"});
        await expectPuppeteer(page).toClick('a span',{text: "Posteingang"});
        await new Promise(r => setTimeout(r, 2000));
    });
    
    test.skip('overwrite email attachment in filemanager from MailDetailsPanel', async () => {
        await new Promise(r => setTimeout(r, 2000));
        await saveAttachment(page);
        await new Promise(r => setTimeout(r, 10000)); //wait for save email.
        await saveAttachment(page);
        await new Promise(r => setTimeout(r, 1000));
        await page.waitForSelector('.x-window.x-window-plain.x-window-dlg');
        await expectPuppeteer(page).toClick('button', {text: 'Ja'});
    });
});

afterAll(async () => {
    browser.close();
});

async function saveAttachment(page) {
    await page.waitForSelector('.tinebase-download-link');
    let attachment = await page.$$('.tinebase-download-link');
    await attachment[1].hover();
    await attachment[1].click('tinebase-download-link-wait');
    await new Promise(r => setTimeout(r, 2000));
    await expectPuppeteer(page).toClick('.x-menu-item-text', {text: 'Speichern als', visible: true});
    await new Promise(r => setTimeout(r, 1000));
    await expectPuppeteer(page).toClick('.x-menu-item-text', {text: 'Datei (im Dateimanager) ...', visible: true});
    await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
    await expectPuppeteer(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 1});
    await expectPuppeteer(page).toClick('button', {text: 'Ok'});
}
