import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

/**
 * Filter toolbar and quick filter (Tine.widgets.grid.FilterToolbarQuickFilterPlugin), tested with Inventory.
 *
 * The quick filter (search field above the grid) is synced with exactly one filter row of the filter toolbar: the
 * first row with the field "Schnellsuche" (query). Further "Schnellsuche" rows (a new row gets this field by default)
 * are independent filters and must not change the quick filter or the synced row.
 */

const APP = '.t-app-inventory';
const QUICK_FILTER = `${APP} input.x-form-search-trigger`;
// the clear trigger is shown next to the quick filter as soon as it has a value
const QUICK_FILTER_CLEAR = `${APP} .x-form-field-wrap:has(> input.x-form-search-trigger) .x-form-clear-trigger`;
// the magnifier next to the quick filter starts the search (the input has the trigger class too)
const QUICK_FILTER_SEARCH = `${APP} .x-form-field-wrap:has(> input.x-form-search-trigger) img.x-form-search-trigger`;

// unique per run, the items of this run are found with it (and deleted again in afterAll)
const PREFIX = 'e2eftb' + Math.round(Math.random() * 10000000);
const ITEMS = ['Laptop', 'Monitor', 'Drucker'].map((name) => `${PREFIX} ${name}`);

const deleteItems = async (prefix) => {
    const result = await Tine.Inventory.searchInventoryItems([{field: 'name', operator: 'startswith', value: prefix}], {});
    const ids = (result.results || []).map((item) => item.id);
    if (ids.length) await Tine.Inventory.deleteInventoryItems(ids);
};

/**
 * selector of the n-th (0 based) filter row of the filter toolbar
 */
const rowSelector = async (index) => {
    const id = await global.page.evaluate((app, idx) => {
        return document.querySelectorAll(`${app} tr.fw-ftb-frow`)[idx]?.id;
    }, APP, index);
    if (!id) throw new Error(`filter row ${index} not found`);
    return `${APP} tr[id="${id}"]`;
};

/**
 * what the user sees: field and value of each filter row and the value of the quick filter
 */
const getState = async () => global.page.evaluate((app, quickFilter) => {
    // an empty field shows its empty text (e.g. "Suchfilter eingeben") as value, marked with x-form-empty-field
    const valueOf = (input) => (!input || input.classList.contains('x-form-empty-field')) ? '' : input.value;
    return {
        rows: Array.from(document.querySelectorAll(`${app} tr.fw-ftb-frow`)).map((row) => ({
            field: valueOf(row.querySelector('td.tw-ftb-frow-field input')),
            value: valueOf(row.querySelector('td.tw-ftb-frow-value input')),
        })),
        quickFilter: valueOf(document.querySelector(quickFilter)),
    };
}, APP, QUICK_FILTER);

/**
 * names of the items of this run in the grid, sorted
 */
const getGridItems = async () => global.page.evaluate((app, prefix) => {
    return Array.from(document.querySelectorAll(`${app} .x-grid3-row .x-grid3-col-name`))
        .map((el) => el.textContent.replace(/\s+/g, ' ').trim())
        .filter((name) => name.startsWith(prefix))
        .sort();
}, APP, PREFIX);

/**
 * waits until the grid shows exactly the given items of this run (items of other users / runs are ignored)
 */
const waitForGridItems = async (expected) => {
    const sorted = [...expected].sort();
    try {
        await global.page.waitForFunction((app, prefix, exp) => {
            const names = Array.from(document.querySelectorAll(`${app} .x-grid3-row .x-grid3-col-name`))
                .map((el) => el.textContent.replace(/\s+/g, ' ').trim())
                .filter((name) => name.startsWith(prefix))
                .sort();
            return JSON.stringify(names) === JSON.stringify(exp);
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}, APP, PREFIX, sorted);
    } catch (err) {
        const error = new Error(`grid does not show ${JSON.stringify(sorted)} but ${JSON.stringify(await getGridItems())}, `
            + `filter: ${JSON.stringify(await getState())}`);
        error.cause = err;
        throw error;
    }
};

/**
 * runs a user action which starts a search and waits until the search is done and the grid shows the expected items.
 * Waiting for the grid content alone is not enough: if the result does not change, the grid shows it before the search
 * is done, and the filter toolbar is updated after the response (e.g. empty rows are removed).
 */
const search = async (action, expected) => {
    const response = global.page.waitForResponse(
        (res) => (res.request().postData() || '').includes('"Inventory.searchInventoryItems"'),
        {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}
    );
    await action();
    await response;
    await waitForGridItems(expected);
};

const clickStartSearch = () => lib.clickOnElementWithLabel(global.page, `${APP} .x-btn-text`, 'Suche starten');

const fillRowValue = async (index, value) => {
    await lib.formFillInputField(global.page, {selector: `${await rowSelector(index)} td.tw-ftb-frow-value input`, value});
};

const addRowAfter = async (index) => {
    const rowCount = (await getState()).rows.length;
    await lib.clickOnElement(global.page, `${await rowSelector(index)} .action_addFilter`);
    await global.page.waitForFunction((app, count) => document.querySelectorAll(`${app} tr.fw-ftb-frow`).length === count,
        {}, APP, rowCount + 1);
};

/**
 * selects another field (e.g. "Name") for a filter row, like a user: open the field combo, click the list item
 */
const changeRowField = async (index, field) => {
    const row = await rowSelector(index);
    await lib.clickOnElement(global.page, `${row} td.tw-ftb-frow-field .x-form-trigger`);
    // the combo list is rendered into <body>, only the visible one is the list of this combo
    await expectPuppeteer(global.page).toClick('.x-combo-list-item', {text: field, visible: true});
    await global.page.waitForFunction((sel, f) => document.querySelector(sel)?.value === f,
        {}, `${row} td.tw-ftb-frow-field input`, field);
};

/**
 * "delete all filters" leaves one empty "Schnellsuche" row, which is the row synced with the quick filter.
 * The button is only shown if there is more than one row (e.g. Inventory starts with a single "Status" row).
 */
const resetFilters = async () => {
    if ((await getState()).rows.length < 2) {
        await addRowAfter(0);
    }
    await search(() => lib.clickOnElement(global.page, `${APP} .action_delAllFilter`), ITEMS);
    expect(await getState()).toEqual({rows: [{field: 'Schnellsuche', value: ''}], quickFilter: ''});
};

let filterToolbarWasVisible = true;

beforeAll(async () => {
    await lib.getBrowser('Inventarisierung');
    // the json api functions (Tine.Inventory.*) are created from the service map of the registry
    await global.page.waitForFunction(() => typeof Tine.Inventory?.searchInventoryItems === 'function');
    await global.page.evaluate(deleteItems, PREFIX);
    await global.page.evaluate(async (names) => {
        for (const name of names) await Tine.Inventory.saveInventoryItem({name});
    }, ITEMS);

    // the visibility of the filter toolbar is persisted for the user, restore it in afterAll
    filterToolbarWasVisible = !!await global.page.$(`${APP} table.x-btn.x-btn-pressed:has(.action_filter)`);
    await lib.ensureFilterToolbarVisible(global.page, APP);
    await resetFilters();
});

afterAll(async () => {
    try {
        if (global.page) {
            await resetFilters().catch(() => {});
            await lib.ensureFilterToolbarVisible(global.page, APP, {visible: filterToolbarWasVisible}).catch(() => {});
            await global.page.evaluate(deleteItems, PREFIX);
        }
    } finally {
        await lib.closeBrowser();
    }
});

describe('quick filter sync with the first quick search row', () => {
    test('a search in the first row sets the quick filter', async () => {
        await fillRowValue(0, ITEMS[0]);
        await search(clickStartSearch, [ITEMS[0]]);

        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[0]}],
            quickFilter: ITEMS[0],
        });
    });

    test('adding a row keeps the value of the first row and the quick filter', async () => {
        // the new row is a "Schnellsuche" row too (default field), but it is not synced with the quick filter
        await addRowAfter(0);

        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[0]}, {field: 'Schnellsuche', value: ''}],
            quickFilter: ITEMS[0],
        });
    });

    test('a value in a further quick search row does not change the quick filter', async () => {
        // both rows have to match (AND): PREFIX matches all items, the first row only one
        await fillRowValue(1, PREFIX);
        await search(clickStartSearch, [ITEMS[0]]);

        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[0]}, {field: 'Schnellsuche', value: PREFIX}],
            quickFilter: ITEMS[0],
        });
    });

    test('a search in the quick filter sets the first row', async () => {
        await resetFilters();

        await lib.formFillInputField(global.page, {selector: QUICK_FILTER, value: ITEMS[1]});
        await search(() => lib.clickOnElement(global.page, QUICK_FILTER_SEARCH), [ITEMS[1]]);

        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[1]}],
            quickFilter: ITEMS[1],
        });
    });

    test('clearing the quick filter clears the first row', async () => {
        await search(() => lib.clickOnElement(global.page, QUICK_FILTER_CLEAR), ITEMS);

        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ''}],
            quickFilter: '',
        });
    });

    test('a row changed to quick search is synced with the quick filter', async () => {
        // after changing the field to another one, there is no quick search row left
        await changeRowField(0, 'Name');
        await changeRowField(0, 'Schnellsuche');

        await fillRowValue(0, ITEMS[2]);
        await search(clickStartSearch, [ITEMS[2]]);
        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[2]}],
            quickFilter: ITEMS[2],
        });

        await addRowAfter(0);
        expect(await getState()).toEqual({
            rows: [{field: 'Schnellsuche', value: ITEMS[2]}, {field: 'Schnellsuche', value: ''}],
            quickFilter: ITEMS[2],
        });
    });
});
