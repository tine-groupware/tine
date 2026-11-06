import lib from '../../lib/browser.js';

// colors of event types: color swatches in the grid and the color menu in the edit dialog (Stammdaten > Kalender > Terminarten)
// NOTE: dialogs open as browser popups (default of preference windowtype)
const suffix = Math.round(Math.random() * 10000000);
const eventTypes = [
    {name: `Mit Farbe ${suffix}`, color: '#FF6600'},
    {name: `Ohne Farbe ${suffix}`},
];
const newEventTypeName = `Neue Farbe ${suffix}`;

const APP = '.t-app-coredata';
const MENU = '.x-color-menu';
const PICKER_DIALOG = '.x-window:has(.saturation)';

// event type edit dialog (popup)
let dialog = null;

const getRows = () => global.page.evaluate((app) => Array.from(document.querySelectorAll(`${app} .x-grid3-row`)).map((row) => {
    const swatch = row.querySelector('.x-grid3-col-color .tb-color-swatch');
    const style = swatch && getComputedStyle(swatch);
    return {
        name: row.querySelector('.x-grid3-col-name').textContent.trim(),
        height: Math.round(row.getBoundingClientRect().height),
        swatch: swatch && {
            background: style.backgroundColor,
            width: Math.round(swatch.getBoundingClientRect().width),
            borderRadius: style.borderRadius,
        },
    };
}), APP);

const getRow = async (name) => (await getRows()).find((row) => row.name === name);

const getColorFieldBackground = () => dialog.evaluate(() => getComputedStyle(document.querySelector('input[name="color"]')).backgroundColor);

// NOTE: waitForSelector with visible misses the Ext menu sometimes, so check the visibility explicitly
const waitForMenu = () => dialog.waitForFunction((menu) => {
    const el = document.querySelector(menu);
    return !!el && getComputedStyle(el).visibility === 'visible' && el.getBoundingClientRect().height > 0;
}, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE'), polling: 'raf'}, MENU);

const waitForMenuHidden = () => dialog.waitForFunction((menu) => {
    const el = document.querySelector(menu);
    return !el || getComputedStyle(el).visibility !== 'visible' || el.getBoundingClientRect().height === 0;
}, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE'), polling: 'raf'}, MENU);

// click outside of the menu
// NOTE: Ext.menu.MenuMgr ignores clicks within 50ms after the menu was shown, so click again if needed
const closeMenu = async () => {
    for (let attempt = 0; attempt < 5; attempt++) {
        await lib.clickOnElement(dialog, 'input[name="name"]');
        try {
            await dialog.waitForFunction((menu) => getComputedStyle(document.querySelector(menu)).visibility !== 'visible', {timeout: 500}, MENU);
            return;
        } catch (e) {
            // menu still shown
        }
    }
    await waitForMenuHidden();
};

const openMenu = async () => {
    await lib.clickOnElement(dialog, '.x-form-trigger.action_color_picker');
    await waitForMenu();
};

const selectColor = async (color) => {
    await openMenu();
    await lib.clickOnElement(dialog, `${MENU} a.color-${color}`);
    await waitForMenuHidden();
};

const toRgb = (hex) => {
    const [r, g, b] = hex.replace('#', '').match(/[0-9a-fA-F]{2}/g).map((v) => parseInt(v, 16));
    return `rgb(${r}, ${g}, ${b})`;
};

beforeAll(async () => {
    await lib.getBrowser('Stammdaten');
    // test data
    await global.page.evaluate(async (types) => {
        window.__e2eEventTypes = [];
        for (const type of types) {
            window.__e2eEventTypes.push(await Tine.Calendar.saveEventType(type));
        }
    }, eventTypes);

    await lib.clickOnElementWithLabel(global.page, '.tine-mainscreen-centerpanel-west .x-tree-node a span', 'Kalender');
    await lib.waitForAppReady(global.page);
    await lib.clickOnElementWithLabel(global.page, '.tine-mainscreen-centerpanel-west .x-tree-node a span', 'Terminarten');
    await global.page.waitForFunction((app) => Array.from(document.querySelectorAll(`${app} .x-btn-text`)).some((el) => el.textContent.trim() === 'Terminart hinzufügen' && el.offsetParent),
        {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, APP);
    await lib.waitForAppReady(global.page);
});

describe('Event type colors in the grid', () => {
    test('shows a round color swatch', async () => {
        await global.page.waitForFunction((app, name) => Array.from(document.querySelectorAll(`${app} .x-grid3-col-name`)).some((el) => el.textContent.trim() === name),
            {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}, APP, eventTypes[0].name);

        expect((await getRow(eventTypes[0].name)).swatch).toStrictEqual({background: 'rgb(255, 102, 0)', width: 14, borderRadius: '50%'});
    });

    test('shows nothing if there is no color', async () => {
        expect((await getRow(eventTypes[1].name)).swatch).toBeNull();
    });

    test('does not change the row height', async () => {
        // regression: rows with swatch were 2px higher
        expect((await getRow(eventTypes[0].name)).height).toBe((await getRow(eventTypes[1].name)).height);
    });
});

describe('Color menu in the event type dialog', () => {
    test('open event type dialog', async () => {
        dialog = await lib.getEditDialog('Terminart hinzufügen', null, {selector: `${APP} .x-btn-text`});
        await dialog.waitForSelector('.x-form-trigger.action_color_picker', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        await lib.formFillInputField(dialog, {selector: 'input[name="name"]', value: newEventTypeName});
    });

    test('shows the palette as grid of round swatches', async () => {
        await openMenu();

        const palette = await dialog.evaluate((menu) => {
            const el = document.querySelector(`${menu} .x-color-palette`);
            const swatch = el.querySelector('a.color-FF6600');
            return {
                swatches: el.querySelectorAll('a').length,
                columns: getComputedStyle(el).gridTemplateColumns.split(' ').length,
                swatchSize: Math.round(swatch.getBoundingClientRect().width),
                borderRadius: getComputedStyle(swatch).borderRadius,
                menuBorderRadius: parseFloat(getComputedStyle(document.querySelector(menu)).borderRadius),
            };
        }, MENU);

        expect(palette).toMatchObject({swatches: 48, columns: 8, swatchSize: 22, borderRadius: '50%'});
        // bootstrap dropdown look
        expect(palette.menuBorderRadius).toBeGreaterThan(0);

        await closeMenu();
    });

    test('sets the color on click', async () => {
        await selectColor('339966');
        expect(await getColorFieldBackground()).toBe('rgb(51, 153, 102)');
    });

    test('marks the selected color without clipping the selection ring', async () => {
        // last column
        await selectColor('808080');
        await openMenu();

        const result = await dialog.evaluate((menu) => {
            const ring = 4;
            const selected = Array.from(document.querySelectorAll(`${menu} a.x-color-palette-sel`));
            const swatch = selected[0].getBoundingClientRect();
            const rect = document.querySelector(menu).getBoundingClientRect();
            return {
                selected: selected.map((a) => a.className),
                insideMenu: swatch.left - ring >= rect.left && swatch.right + ring <= rect.right
                    && swatch.top - ring >= rect.top && swatch.bottom + ring <= rect.bottom,
            };
        }, MENU);

        expect(result).toStrictEqual({selected: ['color-808080 x-color-palette-sel'], insideMenu: true});
    });

    test('selects a custom color with the color picker', async () => {
        await lib.clickOnElement(dialog, `${MENU} a.color-picker`);
        await dialog.waitForSelector(`${PICKER_DIALOG} .saturation`, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});

        // pick a color in the saturation area
        const box = await (await dialog.$(`${PICKER_DIALOG} .saturation`)).boundingBox();
        await dialog.mouse.click(box.x + box.width * 0.7, box.y + box.height * 0.3);
        await lib.clickOnElementWithLabel(dialog, `${PICKER_DIALOG} button`, 'Ok');
        await dialog.waitForSelector(PICKER_DIALOG, {hidden: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});

        await dialog.waitForFunction(() => getComputedStyle(document.querySelector('input[name="color"]')).backgroundColor !== 'rgb(128, 128, 128)',
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
        const custom = await getColorFieldBackground();

        // custom color is shown and marked in the picker swatch
        await openMenu();
        const picker = await dialog.evaluate((menu) => {
            const a = document.querySelector(`${menu} a.color-picker`);
            return {selected: a.classList.contains('x-color-palette-sel'), background: getComputedStyle(a.querySelector('span')).backgroundColor};
        }, MENU);
        expect(picker).toStrictEqual({selected: true, background: custom});

        await closeMenu();
    });

    test('shows the selected color in the grid after saving', async () => {
        const custom = await getColorFieldBackground();
        await lib.clickOnElementWaitPageClosed(dialog, '.x-toolbar-right-row button', 'Ok');

        await global.page.waitForFunction((app, name) => Array.from(document.querySelectorAll(`${app} .x-grid3-col-name`)).some((el) => el.textContent.trim() === name),
            {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}, APP, newEventTypeName);
        expect((await getRow(newEventTypeName)).swatch.background).toBe(custom);
        lib.assertNotAborted('Color menu in the event type dialog');
    });
});

afterAll(async () => {
    await global.page.evaluate(async (name) => {
        const created = (await Tine.Calendar.searchEventTypes([], {})).results.filter((type) => type.name === name);
        const ids = [...(window.__e2eEventTypes || []), ...created].map((type) => type.id);
        await Tine.Calendar.deleteEventTypes(ids);
    }, newEventTypeName);
    await lib.closeBrowser();
});
