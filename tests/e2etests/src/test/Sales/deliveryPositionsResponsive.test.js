import lib from '../../lib/browser.js';

// Responsive layout levels of the positions grid (quickadd + grouping grid) in the delivery dialog:
// - levels follow the popup size (see UI_CONFIG in Sales/Model/DocumentPosition/Delivery.php)
// - levels can be chosen manually in the grid's column menu and are remembered

// NOTE: columns not available in every installation (e.g. evaluation dimensions) are ignored
const levelColumns = {
    small: ['title', 'quantity', 'unit'],
    medium: ['pos_number', 'type', 'title', 'quantity', 'unit'],
    big: ['pos_number', 'type', 'title', 'quantity', 'unit', 'eval_dim_cost_center', 'product_id'],
};
// popup widths resulting in the given level (the dialog's side panel takes some space)
const levelWidths = {small: 500, medium: 800, big: 1500, large: 2300};
const popupHeight = 900;

const gridSelector = '.x-grid-panel.x-grid3-quickadd';
const menuButtonSelector = `${gridSelector} .ext-ux-grid-gridviewmenuplugin-menuBtn`;

beforeAll(async () => {
    await lib.getBrowser('Sales', 'Lieferungen');
});

/**
 * returns responsive mode and visible columns (from the rendered header) of the positions grid
 */
const getGridState = (page) => page.evaluate((gridSelector) => {
    const gridEl = document.querySelector(gridSelector);
    const grid = Ext.getCmp(gridEl.id);
    const view = grid.getView();
    const visibleColumns = Array.from(view.mainHd.dom.querySelectorAll('.x-grid3-hd-row td'))
        .filter((td) => getComputedStyle(td).display !== 'none')
        .map((td) => (Array.from(td.classList).find((cls) => cls.startsWith('x-grid3-td-')) || '').replace('x-grid3-td-', ''))
        .filter(Boolean);
    return {
        mode: view.getResponsiveMode().name,
        manualMode: view.responsiveMode,
        allColumns: grid.getColumnModel().config.map((col) => col.id),
        visibleColumns,
    };
}, gridSelector);

const waitForMode = async (page, mode) => {
    try {
        await page.waitForFunction((gridSelector, mode) => {
            const gridEl = document.querySelector(gridSelector);
            return gridEl && Ext.getCmp(gridEl.id)?.getView().getResponsiveMode().name === mode;
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, gridSelector, mode);
    } catch (err) {
        const state = await getGridState(page).catch(() => null);
        const error = new Error(`waitForMode: grid did not switch to "${mode}" (state: ${JSON.stringify(state)})`);
        error.cause = err;
        throw error;
    }
};

const resizePopup = async (page, width) => {
    await page.setViewport({width, height: popupHeight});
};

/**
 * expected visible columns of a level, restricted to the columns the grid actually has
 */
const expectedColumns = (state, level) => levelColumns[level].filter((col) => state.allColumns.includes(col)).sort();

const expectLevelColumns = (state, level) => {
    expect(state.visibleColumns.slice().sort()).toEqual(expectedColumns(state, level));
};

/**
 * labels of the items in the shown menus
 * NOTE: hidden ext menus stay in the dom and are moved out of the viewport (x-hide-offsets)
 */
const shownMenuItems = (checkedOnly) => Array.from(document.querySelectorAll('.x-menu'))
    .filter((menu) => {
        const style = getComputedStyle(menu);
        const rect = menu.getBoundingClientRect();
        return style.visibility !== 'hidden' && style.display !== 'none' && rect.width > 0 && rect.right > 0 && rect.bottom > 0;
    })
    .flatMap((menu) => Array.from(menu.querySelectorAll(checkedOnly ? '.x-menu-item-checked .x-menu-item-text' : '.x-menu-item-text')))
    .map((el) => el.textContent.replace(/\s+/g, ' ').trim());

const getShownMenuItems = (page, {checkedOnly = false} = {}) => page.evaluate(
    `(${shownMenuItems.toString()})(${JSON.stringify(checkedOnly)})`
);

const waitForMenu = async (page, shown, timeout = lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')) => {
    try {
        await page.waitForFunction(
            `(${shownMenuItems.toString()})(false).length > 0 === ${JSON.stringify(shown)}`,
            {timeout}
        );
    } catch (err) {
        const error = new Error(`waitForMenu: menu not ${shown ? 'shown' : 'hidden'}`);
        error.cause = err;
        throw error;
    }
};

const openMenu = async (page) => {
    // NOTE: the menu button ignores clicks shortly (500ms) after the menu got hidden -> click again
    const deadline = Date.now() + lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE');
    while (true) {
        await lib.clickOnElement(page, menuButtonSelector);
        try {
            await waitForMenu(page, true, 1000);
            return;
        } catch (err) {
            if (Date.now() > deadline) throw err;
        }
    }
};

const closeMenu = async (page) => {
    await page.evaluate(() => Ext.menu.MenuMgr.hideAll());
    await waitForMenu(page, false);
};

const chooseLevel = async (page, label) => {
    await openMenu(page);
    await lib.clickOnElementWithLabel(page, '.x-menu-item-text', label);
    await waitForMenu(page, false);
};

// "Auto" item shows the current level, e.g. "Auto (Large)"
const getAutoLabel = (mode) => `Auto (${mode.charAt(0).toUpperCase()}${mode.slice(1)})`;

/**
 * the chosen level is remembered for the user -> make sure tests start in auto mode
 */
const resetToAuto = async (page) => {
    const state = await getGridState(page);
    if (state.manualMode && state.manualMode !== 'auto') {
        await chooseLevel(page, getAutoLabel(state.mode));
        await page.waitForFunction((gridSelector) => {
            const gridEl = document.querySelector(gridSelector);
            return Ext.getCmp(gridEl.id).getView().responsiveMode === 'auto';
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, gridSelector);
    }
};

// NOTE: columns are check items too -> only consider the level items
const getCheckedLevel = async (page) => (await getShownMenuItems(page, {checkedOnly: true}))
    .filter((label) => /^(Auto \(.+\)|Small|Medium|Big|Large)$/.test(label));

let popupWindow = null;

describe('Delivery positions grid responsive levels', () => {
    test('Open dialog', async () => {
        popupWindow = await lib.getEditDialog('Lieferung hinzufügen');
        await lib.waitForElement(popupWindow, gridSelector, {visible: true});
        await resetToAuto(popupWindow);
    });

    test('Levels follow the popup size', async () => {
        for (const level of ['small', 'medium', 'big', 'large', 'medium', 'small']) {
            await resizePopup(popupWindow, levelWidths[level]);
            await waitForMode(popupWindow, level);

            const state = await getGridState(popupWindow);
            if (level === 'large') {
                // large shows all remaining columns
                expect(state.visibleColumns).toEqual(expect.arrayContaining(expectedColumns(state, 'big')));
                expect(state.visibleColumns.length).toBeGreaterThan(expectedColumns(state, 'big').length);
            } else {
                expectLevelColumns(state, level);
            }
            // technical / generic columns are never shown automatically
            expect(state.visibleColumns).toEqual(expect.not.arrayContaining(['grouping', 'sorting', 'created_by', 'last_modified_time']));
        }
    });

    test('Column menu offers the levels', async () => {
        await resizePopup(popupWindow, levelWidths.large);
        await waitForMode(popupWindow, 'large');
        await openMenu(popupWindow);

        const labels = await getShownMenuItems(popupWindow);
        expect(labels).toEqual(expect.arrayContaining(['Auto (Large)', 'Small', 'Medium', 'Big', 'Large']));
        // editing is not possible in one column mode -> not offered for quickadd grids
        expect(labels).toEqual(expect.not.arrayContaining(['One column']));
        expect(await getCheckedLevel(popupWindow)).toEqual(expect.arrayContaining(['Auto (Large)']));

        await closeMenu(popupWindow);
    });

    test('Manually chosen level overrides the popup size', async () => {
        await chooseLevel(popupWindow, 'Small');
        await waitForMode(popupWindow, 'small');
        expectLevelColumns(await getGridState(popupWindow), 'small');

        // resizing keeps the manual level
        await resizePopup(popupWindow, levelWidths.big);
        await waitForMode(popupWindow, 'small');
        expectLevelColumns(await getGridState(popupWindow), 'small');

        await openMenu(popupWindow);
        expect(await getCheckedLevel(popupWindow)).toEqual(['Small']);
        await closeMenu(popupWindow);

        await chooseLevel(popupWindow, 'Medium');
        await waitForMode(popupWindow, 'medium');
        expectLevelColumns(await getGridState(popupWindow), 'medium');
    });

    test('Manually chosen level is remembered', async () => {
        await popupWindow.close();
        popupWindow = await lib.getEditDialog('Lieferung hinzufügen');
        await lib.waitForElement(popupWindow, gridSelector, {visible: true});
        await resizePopup(popupWindow, levelWidths.large);

        await waitForMode(popupWindow, 'medium');
        const state = await getGridState(popupWindow);
        expect(state.manualMode).toEqual('medium');
        expectLevelColumns(state, 'medium');
    });

    test('Auto follows the popup size again', async () => {
        await chooseLevel(popupWindow, getAutoLabel('medium'));
        await waitForMode(popupWindow, 'large');

        await resizePopup(popupWindow, levelWidths.small);
        await waitForMode(popupWindow, 'small');
        expectLevelColumns(await getGridState(popupWindow), 'small');
    });
});

afterAll(async () => {
    // don't leave a manually chosen level for the test user
    if (popupWindow && !popupWindow.isClosed()) {
        await resetToAuto(popupWindow).catch(() => {});
    }
    await lib.closeBrowser();
});
