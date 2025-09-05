const expect = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getSetup();
    expect.setDefaultOptions({timeout: 1000});
});

describe('conditions', () => {
    test('accept conditions', async () => {
        try {
            await expect(page).toClick('a > span', {text: 'Bedingungen und Konditionen'});
            await expect(page).toMatchElement('input[name="acceptLicense"]');
            await expect(page).toClick('input[name="acceptLicense"]', {visible: true});
            await expect(page).toMatchElement('input[name="acceptLicense"]');
            await expect(page).toClick('input[name="acceptPrivacy"]', {visible: true});
            await expect(page).toClick('button', {text: 'Bedingungen und Konditionen akzeptieren', visible: true});
        } catch (e) {
            console.log('condition also accepted')
        }
    });
});

describe('setup checks', () => {
    test('main page ', async () => {
        await expect(page).toClick('a > span', {text: 'Setup Tests'});
        await expect(page).toMatchElement('table.x-grid3-row-table td.x-grid3-td-key div.x-grid3-col-key', {text: 'Database'});
        await expect(page).toClick('button', {text: 'Setup Tests ausführen'});
    });
});

describe('config manager', () => {
    test('main page', async () => {
        await expect(page).toClick('a > span', {text: 'Konfigurationsverwaltung'});
    });
});

describe('authentication/accounts', () => {
    test('main page', async () => {
        await expect(page).toClick('a > span', {text: 'Authentifizierung/Benutzer*innenkonten'});
    });
});

describe('email', () => {
    test('main page', async () => {
        await expect(page).toClick('a > span', {text: 'E-Mail'});
    });
});

describe('Application Manager', () => {
    test('main page', async () => {
        await expect(page).toClick('a > span', {text: 'Anwendungsverwaltung'});
    });
});

afterAll(async () => {
    browser.close();
});
