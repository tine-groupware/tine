import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

//@todo some demodata in editDialog?

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung');
    await lib.makeScreenshot(
        global.page, {path: 'screenshots/Zeiterfassung/1_zeiterfassung_module.png',
        clip: {x: 0, y: 0, width: 150, height: 300}}
    )
});

describe('timeaccount', () => {
    describe('Edit Timeaccount', () => {
        let editDialog;
        test('mainpage Zeitkonten', async () => {
            await lib.clickOnElementWithLabel(global.page, '.t-app-timetracker .tine-mainscreen-centerpanel-west-modules a span', 'Zeitkonten');
        });
        test('open Zeitkonto hinzufügen popup dialog', async () => {
            editDialog = await lib.getEditDialog('Zeitkonto hinzufügen', global.page, {selector: '.t-app-timetracker .tine-mainscreen-centerpanel-north .x-btn-text'});
            await lib.makeScreenshot(editDialog,{path: 'screenshots/Zeiterfassung/2_zeiterfassung_zeitkonto_neu.png'});
        });

        test('permissions tab', async () => {
            await lib.clickOnElementWithLabel(editDialog, '.x-tab-panel-header .x-tab-strip-text', 'Zugriffsrechte');
            await editDialog.waitForSelector('.x-grid3-viewport', { visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY') });
            await lib.makeScreenshot(editDialog,{path: 'screenshots/Zeiterfassung/3_zeiterfassung_zeitkonto_rechte.png'});
            await lib.clickOnElementWaitPageClosed(editDialog, 'button', 'Abbrechen');
        })
    });
});

describe('timetracker', () => {
    describe('Edit Timesheet', () => {
        let editDialog;
        test('mainpage Stundenzettel', async () => {
            await lib.clickOnElementWithLabel(global.page, '.t-app-timetracker .tine-mainscreen-centerpanel-west-modules a span', 'Stundenzettel');
        });

        test('open Stundenzettel hinzufügen popup dialog', async () => {
            editDialog = await lib.getEditDialog('Stundenzettel hinzufügen', global.page, {selector: '.t-app-timetracker .tine-mainscreen-centerpanel-north .x-btn-text'});
            await lib.makeScreenshot(editDialog,{path: 'screenshots/Zeiterfassung/4_zeiterfassung_stundenzettel_neu.png'});
            await lib.clickOnElementWaitPageClosed(editDialog, 'button', 'Abbrechen');
        });
    });
});

afterAll(async () => {
    await lib.closeBrowser();
});
