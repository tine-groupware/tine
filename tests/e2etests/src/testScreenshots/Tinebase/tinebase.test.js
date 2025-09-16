const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');
require('dotenv').config();

beforeAll(async () => {
    //expect.setDefaultOptions({timeout: 1000});
    await lib.getBrowser('Adressbuch');
});

describe('mainScreen', () => {
    //let Apps = ['Admin', 'Adressbuch', 'Dateimanager', 'Kalender', 'Crm', 'Aufgaben', 'E-Mail', 'Sales', 'Human Resources', 'Zeiterfassung', 'Inventarisierung'];
    let Apps = [
        {text:'Admin', id:'admin'},
        {text:'Adressbuch', id:'addressbook'},
        {text:'Aufgaben', id:'tasks'},
        {text:'BeispielAnwendung', id:'exampleapplication'},
        {text:'Crm', id:'crm'},
        {text:'DFCom', id:'dfcom'},
        {text:'Dateimanager', id:'filemanager'},
        {text:'FAQ', id:'simplefaq'},
        {text:'E-Mail', id:'felamimail'},
        {text:'EventManager', id:'eventmanager'},
        {text:'Human Resources', id:'humanresources'},
        {text:'Inventarisierung', id:'inventory'},
        {text:'Kalender', id:'calendar'},
        {text:'Klassen', id:'courses'},
        {text:'Lesezeichen', id:'bookmarks'},
        {text:'Projekte', id:'projects'},
        {text:'Sales', id:'sales'},
        {text:'Stammdaten', id:'coredata'},
        {text:'Zeiterfassung', id:'timetracker'}
    ];
    test('all apps', async () => {
        await page.waitForSelector('.action_menu.application-menu-btn');
        for (let i = 0; i < Apps.length; i++) {
            await expect(page).toClick('.action_menu.application-menu-btn');
            await page.waitForSelector('.popover-body');
            try {
                await expect(page).toClick('.application-menu-item__text', {text: Apps[i].text});
                await page.waitForSelector('#tine-docked-app-'+Apps[i].id);
            } catch (e) {
                //console.log('Application ' + Apps[i] + ' isn\'t install');
            }
        }
        await expect(page).toClick('#tine-docked-app-addressbook');
        await page.waitForSelector('.tine-bar__active-app' , {text: 'Adressbuch'});
        await lib.makeScreenshot(page, 'screenshots/StandardBedienhinweise/1_standardbedienhinweise_alle_reiter.png');
    })
});

describe('usersettings', () => {
    let newPage;
    let settings;
    test('open usersettings', async () => {
        await page.waitForTimeout(2000);
        await page.click('.account-user-avatar');
        await page.waitForTimeout(2000);
        settings = await expect(page).toMatchElement('.main-menu-item.px-3.py-1.d-flex.align-items-center.pe-5 .ms-2', {text: 'Einstellungen', visible: true});
        await settings.hover();
        await lib.makeScreenshot(
            page, 'screenshots/Benutzereinstellungen/1_benutzereinstellungen_link.png',
            {clip: {x: 1000, y: 0, width: 1366 - 1000, height: 100}}
        );
    });
    test('usersettings', async () => {
        newPage = lib.getNewWindow();
        await settings.click();
        newPage = await newPage;
        await newPage.waitForTimeout(5000);
        await lib.makeScreenshot(newPage, 'screenshots/Benutzereinstellungen/2_benutzereinstellungen_generelle_einstellungen.png');
    });

    test('appsettings', async () => {
        await getSettingScreenshots(newPage, 'Mein Profil', '4_benutzereinstellungen_profil');
        await getSettingScreenshots(newPage, 'ActiveSync', '5_benutzereinstellungen_activesync');
        await getSettingScreenshots(newPage, 'Zeiterfassung', '6_benutzereinstellungen_zeiterfassung');
        await getSettingScreenshots(newPage, 'Inventarisierung', '7_benutzereinstellungen_inventar');
        await getSettingScreenshots(newPage, 'E-Mail', '9_benutzereinstellungen_email');
        await getSettingScreenshots(newPage, 'Crm', '10_benutzereinstellungen_crm');
        await getSettingScreenshots(newPage, 'Kalender', '11_benutzereinstellungen_kalender');
        await getSettingScreenshots(newPage, 'Adressbuch', '12_benutzereinstellungen_adressbuch');
        await getSettingScreenshots(newPage, 'Aufgaben', '8_benutzereinstellungen_aufgaben');
    });


    test('admin mode', async () => {
        await expect(newPage).toClick('span', {text: 'Generelle Einstellungen'});
        await newPage.waitForTimeout(2000);
        await newPage.click('.x-btn-image.action_adminMode');
        await newPage.waitForTimeout(2000);
        await lib.makeScreenshot(newPage, 'screenshots/Benutzereinstellungen/3_benutzereinstellungen_generelle_einstellungen_adminmodus.png');
        await expect(newPage).toClick('button', {text: 'Abbrechen'});
    });
});

afterAll(async () => {
    browser.close();
});

async function getSettingScreenshots(newPage, text, screenName) {
    await expect(newPage).toClick('span', {text: text});
    await newPage.waitForTimeout(1000);
    await lib.makeScreenshot(newPage, 'screenshots/Benutzereinstellungen/' + screenName + '.png');
}
