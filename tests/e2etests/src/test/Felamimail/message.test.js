const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('E-Mail');
    await page.waitForSelector('a span',{text: "Posteingang"});
    await expect(page).toClick('a span',{text: "Posteingang"});
    await page.waitForTimeout(2000);
});

describe('message', () => {
    let popupWindow;
    test('compose message with uploaded attachment', async () => {
        popupWindow = await lib.getEditDialog('Verfassen');
        let currentUser = await lib.getCurrentUser(popupWindow);
        // add recipient
        await popupWindow.waitForTimeout(2000);
        let inputFields = await popupWindow.$$('input');
        await inputFields[2].type(currentUser.accountEmailAddress);
        await popupWindow.waitForTimeout(2000); //musst wait for input!
        await popupWindow.waitForSelector('.search-item.x-combo-selected');
        await popupWindow.click('.search-item.x-combo-selected');
        await popupWindow.waitForTimeout(500); //wait for new mail line!
        await popupWindow.click('input[name=subject]');
        await popupWindow.waitForTimeout(1000); //musst wait for input!
        await expect(popupWindow).toFill('input[name=subject]', 'message with attachment');
        await popupWindow.waitForTimeout(1000);

        const fileToUpload = 'src/test/Felamimail/attachment.txt';
        let filePickerWindow = lib.getNewWindow();
        await expect(popupWindow).toClick('.x-btn-text', {text: 'Datei hinzufügen'});
        filePickerWindow = await filePickerWindow;
        await filePickerWindow.waitForTimeout(8000); //musst wait for input!
        let element = await filePickerWindow.$('[ext\\:tree-node-id="myUser"]');
        await element.click({clickCount:2});
        await filePickerWindow.waitForTimeout(5000); //musst wait!
        await expect(filePickerWindow).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 2});
        await filePickerWindow.waitForTimeout(2000); //musst wait!
        await filePickerWindow.waitForSelector('input[type=file]');
        const inputUploadHandle = await filePickerWindow.$('input[type=file]');
        await inputUploadHandle.uploadFile(fileToUpload);
        await filePickerWindow.waitForTimeout(1000);
        await expect(filePickerWindow).toClick('button', {text: 'Abbrechen'});

        let filePickerWindowNew = lib.getNewWindow();
        await expect(popupWindow).toClick('.x-btn-text', {text: 'Datei hinzufügen'});
        filePickerWindowNew = await filePickerWindowNew;
        await filePickerWindowNew.waitForTimeout(8000);
        element = await filePickerWindowNew.$('[ext\\:tree-node-id="myUser"]');
        await element.click({clickCount:2});
        await filePickerWindowNew.waitForTimeout(500);
        await expect(filePickerWindowNew).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 2});
        await filePickerWindowNew.waitForTimeout(1000);
        await expect(filePickerWindowNew).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});
        await expect(filePickerWindowNew).toClick('button', {text: 'Ok'});
        
        await popupWindow.waitForTimeout(2000);
        await expect(popupWindow).toMatchElement('.x-grid3-cell-inner.x-grid3-col-name', {text:'attachment.txt'});
        await popupWindow.waitForTimeout(2000); //musst wait for upload complete!
        
        // send message
        await expect(popupWindow).toClick('button', {text: 'Senden'});
    });

    // test('compose message with filemanager attachment', async () => {
    //     await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
    //     await expect(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
    //    
    //     // @TODO
    // });
    
    let newMail;
    test('fetch messages', async () => {
        await page.waitForTimeout(2000); //wait to close editDialog

        for(let i = 0; i < 10; i++) {
            await page.click('.t-app-felamimail .x-btn-image.x-tbar-loading');
            await page.waitForTimeout(500);
            try{
                await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment', timeout: 2000});
                break;
            } catch(e){
            }
        }

        await page.waitForTimeout(500);
        newMail = await expect(page).toMatchElement('.x-grid3-cell-inner.x-grid3-col-subject', {text: 'message with attachment'});
        await page.waitForTimeout(500);
        await newMail.click();
    });

    test('details panel', async () => {
        await page.waitForSelector('.preview-panel-felamimail');
    });

    test('contextMenu', async () => {
        await newMail.click({button: 'right'});
        await page.screenshot({path: 'screenshots/EMail/17_email_kontextmenu_email.png'});
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
        await expect(popupWindow).toClick('.x-menu-item-text',
            {text: new RegExp('Datei.*'), visible: true});
        await popupWindow.waitForSelector('.x-grid3-row.x-grid3-row-first');
        await popupWindow.click('.x-grid3-row.x-grid3-row-first');
        await expect(popupWindow).toClick('button', {text: 'Ok'});
        await popupWindow.close();
    });

    test.skip('attachment file in filemanager', async () => {
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Dateimanager'});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
        await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 2});
        await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'attachment.txt'});
        await expect(page).toClick('span', {text: process.env.TEST_BRANDING_TITLE});
        await expect(page).toClick('.x-menu-item-text', {text: 'Felamimail'});
        await page.waitForSelector('a span',{text: "Posteingang"});
        await expect(page).toClick('a span',{text: "Posteingang"});
        await page.waitForTimeout(2000);
    });
    
    test.skip('overwrite email attachment in filemanager from MailDetailsPanel', async () => {
        await page.waitForTimeout(2000);
        await saveAttachment(page);
        await page.waitForTimeout(10000); //wait for save email.
        await saveAttachment(page);
        await page.waitForTimeout(1000);
        await page.waitForSelector('.x-window.x-window-plain.x-window-dlg');
        await expect(page).toClick('button', {text: 'Ja'});
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
    await page.waitForTimeout(2000);
    await expect(page).toClick('.x-menu-item-text', {text: 'Speichern als', visible: true});
    await page.waitForTimeout(1000);
    await expect(page).toClick('.x-menu-item-text', {text: 'Datei (im Dateimanager) ...', visible: true});
    await page.waitForSelector('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER});
    await expect(page).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text: 'Persönliche Dateien von ' + process.env.TEST_USER, clickCount: 1});
    await expect(page).toClick('button', {text: 'Ok'});
}
