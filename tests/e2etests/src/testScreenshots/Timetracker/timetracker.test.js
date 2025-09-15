const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

//@todo some demodata in editDialog?

beforeAll(async () => {
    await lib.getBrowser('Zeiterfassung');
    await lib.makeScreenshot(
        page, 'screenshots/Zeiterfassung/1_zeiterfassung_module.png',
        clip, {x: 0, y: 0, width: 150, height: 300}
    )
});

describe('timeaccount', () => {
    describe('Edit Timeaccount', () => {
        let editDialog;
        test('mainpage', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Zeitkonten'});
        });
        test('open EditDialog', async () => {
            await page.waitForTimeout(1000);
            editDialog = await lib.getEditDialog('Zeitkonto hinzufügen');
            await editDialog.screenshot({path: 'screenshots/Zeiterfassung/2_zeiterfassung_zeitkonto_neu.png'});
        });

        test('permissions', async () => {
            await expect(editDialog).toClick('span', {text: 'Zugriffsrechte'});
            await editDialog.waitForSelector('.x-grid3-viewport');
            await editDialog.screenshot({path: 'screenshots/Zeiterfassung/3_zeiterfassung_zeitkonto_rechte.png'});
            await expect(editDialog).toClick('button', {text: 'Abbrechen'});
        })
    });
});

describe.skip('timetracker', () => {
    describe('Edit Timesheet', () => {
        let editDialog;
        test('mainpage', async () => {
            await expect(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Stundenzettel'});
        });

        test('open EditDialog', async () => {
            await page.waitForTimeout(1000);
            editDialog = await lib.getEditDialog('Stundenzettel hinzufügen');
            await editDialog.screenshot({path: 'screenshots/Zeiterfassung/4_zeiterfassung_stundenzettel_neu.png'});
            await expect(editDialog).toClick('button', {text: 'Abbrechen'});
        });
    });
});

afterAll(async () => {
    browser.close();
});
