import lib from '../../lib/browser.js';

// the main menu has to be shown above Ext windows
// NOTE: dialogs open as browser popups by default, so the test switches the preference windowtype to Ext windows
const MENU = '.t-main-menu.show';
const DIALOG = '.x-window:has(input[name="summary"])';

const openMenu = async () => {
    await lib.clickOnElement(global.page, '.account-btn');
    await global.page.waitForSelector(MENU, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
};

// NOTE: the menu handles escape only if it got the focus
const pressEscape = async () => {
    await global.page.waitForFunction((sel) => !!document.activeElement?.closest(sel), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, MENU);
    await global.page.keyboard.press('Escape');
};

const waitForMenuHidden = () => global.page.waitForFunction((sel) => !document.querySelector(sel), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, MENU);

let originalWindowType = null;

beforeAll(async () => {
    await lib.getBrowser('Kalender');
    originalWindowType = await global.page.evaluate(() => Tine.Tinebase.registry.get('preferences').get('windowtype'));
    if (originalWindowType !== 'Ext') {
        // NOTE: like the preferences dialog: save, update the registry, the client reloads itself (and its cached registry)
        await Promise.all([
            global.page.waitForNavigation({waitUntil: 'domcontentloaded', timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}),
            global.page.evaluate(async () => {
                await Tine.Tinebase.savePreferences({Tinebase: {windowtype: {value: 'Ext'}}});
                Tine.Tinebase.registry.get('preferences').replace('windowtype', 'Ext');
            }),
        ]);
        await lib.waitForPageHeader(global.page);
    }
    await global.page.waitForFunction(() => Tine.WindowFactory?.windowType === 'Ext', {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
});

describe('TineBar main menu', () => {
    test('open event dialog', async () => {
        await lib.clickOnElementWithLabel(global.page, '.t-app-calendar .x-btn-text', 'Termin hinzufügen');
        await global.page.waitForSelector(DIALOG, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        await lib.waitForAppReady(global.page);
    });

    test('is shown above dialogs', async () => {
        await openMenu();

        const zIndices = await global.page.evaluate((menu, dialog) => ({
            menu: parseInt(getComputedStyle(document.querySelector(menu)).zIndex, 10),
            dialog: parseInt(getComputedStyle(document.querySelector(dialog)).zIndex, 10),
        }), MENU, DIALOG);
        expect(zIndices.menu).toBeGreaterThan(zIndices.dialog);

        // menu is clickable above the dialog
        const topElement = await global.page.evaluate((menu) => {
            const rect = document.querySelector(`${menu} .main-menu-item`).getBoundingClientRect();
            return !!document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2).closest(menu);
        }, MENU);
        expect(topElement).toBe(true);
    });

    test('is hidden by escape', async () => {
        await pressEscape();
        await waitForMenuHidden();
    });

    test('is hidden by a click outside', async () => {
        await openMenu();
        await lib.clickOnElement(global.page, `${DIALOG} .x-window-header-text`);
        await waitForMenuHidden();
    });

    test('is shown above the dialog again after the dialog was activated', async () => {
        await openMenu();

        const zIndices = await global.page.evaluate((menu, dialog) => ({
            menu: parseInt(getComputedStyle(document.querySelector(menu)).zIndex, 10),
            dialog: parseInt(getComputedStyle(document.querySelector(dialog)).zIndex, 10),
        }), MENU, DIALOG);
        expect(zIndices.menu).toBeGreaterThan(zIndices.dialog);

        await pressEscape();
        await waitForMenuHidden();
        lib.assertNotAborted('TineBar main menu');
    });
});

afterAll(async () => {
    // close dialog without saving
    if (await global.page.$(DIALOG)) {
        await lib.clickOnElementWithLabel(global.page, `${DIALOG} button`, 'Abbrechen');
    }
    if (originalWindowType && originalWindowType !== 'Ext') {
        await global.page.evaluate(async (value) => {
            await Tine.Tinebase.savePreferences({Tinebase: {windowtype: {value}}});
        }, originalWindowType);
    }
    await lib.closeBrowser();
});
