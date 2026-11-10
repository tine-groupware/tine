import lib from '../../lib/browser.js';
import { expect as expectPuppeteer } from 'expect-puppeteer';

// Tag quick actions:
// - Admin: shared tags can be restricted to models (model picker in context tab)
// - TagsPanel (edit dialogs): tags as labels, pinned tags can be added with one click
// - Tags menu in grid context menus and on click of the tags cell
//   - main screen grids: changes are saved directly
//   - record fields (sales positions): changes are saved with the main record
//
// test data is created via API and removed afterwards

const PREFIX = 'E2E-TAGS';
const sfx = `${PREFIX}-${Math.random().toString(36).substring(2, 7)}`;
const TAG_A = `${sfx} prüfen`;
const TAG_B = `${sfx} anpassen`;
const TAG_ADMIN = `${sfx} admin`;
const CONTACT_A = 'Anna';
const CONTACT_B = 'Bert';
const POS_A = `${sfx} pos A`;
const POS_B = `${sfx} pos B`;
const POSITION_MODEL = 'Sales_Model_DocumentPosition_Offer';

const PINS_STATE = {
    contact: 'tinebase-tags-pins-addressbook-contact',
    position: 'tinebase-tags-pins-sales-documentposition_offer',
};

// recently used tags (RecentsManager) -> restored after the tests
const RECENTS_STATE = {
    addressbook: 'tinebase-tag-addressbook-recents',
    sales: 'tinebase-tag-sales-recents',
};

// pin of a tag which does not exist (any more)
const TAG_DELETED = `${sfx} gelöscht`;

let data = null;
let originalPins = null;
let originalRecents = null;
let originalGridStates = null;

/**
 * delete test data (also leftovers of former runs)
 */
const deleteTestData = async (page) => {
    await page.evaluate(async (prefix) => {
        const offers = await Tine.Sales.searchDocument_Offers([{field: 'customer_id', operator: 'definedBy', value: [
            {field: 'name', operator: 'startswith', value: prefix}]}], {});
        if (offers.results.length) await Tine.Sales.deleteDocument_Offers(_.map(offers.results, 'id'));

        const customers = await Tine.Sales.searchCustomers([{field: 'name', operator: 'startswith', value: prefix}], {});
        if (customers.results.length) await Tine.Sales.deleteCustomers(_.map(customers.results, 'id'));

        const contacts = await Tine.Addressbook.searchContacts([{field: 'n_family', operator: 'startswith', value: prefix}], {});
        if (contacts.results.length) await Tine.Addressbook.deleteContacts(_.map(contacts.results, 'id'));

        const tags = await Tine.Tinebase.searchTags({name: prefix, grant: 'view'}, {});
        const tagIds = _.map(_.filter(tags.results, (tag) => String(tag.name).startsWith(prefix)), 'id');
        if (tagIds.length) await Tine.Admin.deleteTags(tagIds);
    }, PREFIX);
};

const createTestData = async (page) => {
    return page.evaluate(async (sfx, names) => {
        const rights = [{account_id: 0, account_type: 'anyone', view_right: true, use_right: true}];
        const tagA = await Tine.Admin.saveTag({name: names.TAG_A, color: '#e9b200', description: 'e2e', contexts: ['any'], rights});
        const tagB = await Tine.Admin.saveTag({name: names.TAG_B, color: '#2f7de1', description: 'e2e', contexts: ['any'], rights});

        const contactA = await Tine.Addressbook.saveContact({n_given: names.CONTACT_A, n_family: sfx, tags: [tagA]});
        const contactB = await Tine.Addressbook.saveContact({n_given: names.CONTACT_B, n_family: sfx});

        const divisionId = (await Tine.Sales.searchDivisions([], {})).results[0].id;
        const customer = await Tine.Sales.saveCustomer({name: `${sfx} customer`, debitors: [{name: `${sfx} debitor`, division_id: divisionId}]});
        const offer = await Tine.Sales.saveDocument_Offer({
            customer_id: customer,
            division_id: divisionId,
            offer_status: 'DRAFT',
            positions: [
                {title: names.POS_A, type: 'TEXT', sorting: 10000},
                {title: names.POS_B, type: 'TEXT', sorting: 20000},
            ]
        });

        const pick = (tag) => _.pick(tag, ['id', 'name', 'color', 'type', 'description']);
        return {
            tagA: pick(tagA),
            tagB: pick(tagB),
            contactIds: [contactA.id, contactB.id],
            offerId: offer.id,
            offerNumber: offer.document_number,
        };
    }, sfx, {TAG_A, TAG_B, CONTACT_A, CONTACT_B, POS_A, POS_B});
};

/**
 * pins are a user state -> set them for the tests and restore them afterwards
 */
const getPins = (page, ids = PINS_STATE) => page.evaluate((ids) => {
    return _.mapValues(ids, (id) => Ext.state.Manager.get(id) || null);
}, ids);

// NOTE: popups load the state from the server -> wait until it is saved
const setPins = (page, pins, ids = PINS_STATE) => page.evaluate(async (ids, pins) => {
    const provider = Ext.state.Manager.getProvider();
    for (const [key, id] of Object.entries(ids)) {
        if (pins[key]) {
            await provider.set(id, pins[key]);
        } else {
            await provider.clear(id);
        }
    }
}, ids, pins);

// saved column states of the positions grid (e.g. hidden tags column) -> start with default columns
const POSITIONS_GRID_STATE = 'Sales-Offer Position-EditDialogGridPanel';

const getGridStates = (page) => page.evaluate((prefix) => {
    const state = Ext.state.Manager.getProvider().state;
    return _.pickBy(state, (value, id) => id.startsWith(prefix));
}, POSITIONS_GRID_STATE);

const setGridStates = (page, states) => page.evaluate(async (prefix, states) => {
    const provider = Ext.state.Manager.getProvider();
    for (const id of Object.keys(provider.state).filter((id) => id.startsWith(prefix))) {
        if (!states[id]) await provider.clear(id);
    }
    for (const [id, value] of Object.entries(states)) {
        await provider.set(id, value);
    }
}, POSITIONS_GRID_STATE, states);

const openApp = async (page, app, module) => {
    await lib.openAppMenu(page);
    await expectPuppeteer(page).toClick('div.application-menu-item__text', {text: app});
    await lib.closeModalDialogs(page);
    await lib.waitForAppReady(page);
    await expectPuppeteer(page).toMatchElement('title', {
        text: `${lib.getEnvStr('TEST_BRANDING_TITLE')} - ${app}`,
        timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')
    });
    if (module) {
        await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west .x-tree-node a span', {text: module});
        await lib.waitForAppReady(page);
    }
};

/**
 * quick search in the grid of the given app
 * NOTE: there might be many contacts (e.g. demo data) -> test contacts are not on the first page
 */
const quickSearch = async (page, appSelector, text) => {
    const id = await page.waitForFunction((appSelector) => Array.from(document.querySelectorAll(`${appSelector} input.x-form-text`))
        .find((input) => input.offsetParent && Ext.getCmp(input.id) instanceof Ext.ux.SearchField)?.id,
    {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, appSelector);
    const selector = `#${await id.jsonValue()}`;
    await lib.clickOnElement(page, selector, {clickCount: 3});
    await page.keyboard.press('Backspace');
    if (text) {
        await page.keyboard.type(text, {delay: 20});
    }
    await page.keyboard.press('Enter');
    await lib.waitForAppReady(page);
    if (text) {
        // grid shows only matching rows
        await page.waitForFunction((appSelector, text) => {
            const rows = Array.from(document.querySelectorAll(`${appSelector} .x-grid3-row`)).filter((row) => row.offsetParent);
            return rows.length && rows.every((row) => row.textContent.includes(text));
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}, appSelector, text);
    }
};

const getContactTags = (page) => page.evaluate(async (ids) => {
    return Promise.all(ids.map(async (id) => _.map((await Tine.Addressbook.getContact(id)).tags, 'name').sort()));
}, data.contactIds);

const getPositionTags = (page) => page.evaluate(async (id) => {
    const offer = await Tine.Sales.getDocument_Offer(id);
    return _.fromPairs(_.map(offer.positions, (pos) => [pos.title, _.map(pos.tags, 'name').sort()]));
}, data.offerId);

/**
 * visible grid row containing the given text (in the given container)
 */
const getRowSelector = async (page, text, container = '') => {
    const rowIndex = await page.waitForFunction((text, container) => {
        const rows = Array.from(document.querySelectorAll(`${container} .x-grid3-row`))
            .filter((row) => row.offsetParent && row.textContent.includes(text));
        if (!rows.length) return false;
        rows.forEach((row) => row.setAttribute('data-e2e-row', ''));
        const id = `e2e-${Math.random().toString(36).substring(2, 9)}`;
        rows[0].setAttribute('data-e2e-row', id);
        return id;
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, text, container);
    return `[data-e2e-row="${await rowIndex.jsonValue()}"]`;
};

/**
 * menus which are shown
 * NOTE: hidden ext menus stay in the dom and are moved out of the viewport
 */
const isShown = (el) => {
    const style = getComputedStyle(el);
    const rect = el.getBoundingClientRect();
    return style.visibility !== 'hidden' && style.display !== 'none' && rect.width > 0 && rect.right > 0 && rect.bottom > 0;
};

// NOTE: menus reload the usable tags on show
const waitForTagsMenu = (page) => page.waitForFunction(
    `Array.from(document.querySelectorAll('.tw-tags-menu')).some((el) => (${isShown.toString()})(el) && !el.classList.contains('tw-tags-menu-loading'))`,
    {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}
);

const waitForMenusHidden = (page) => page.waitForFunction(
    `!Array.from(document.querySelectorAll('.x-menu')).some(${isShown.toString()})`,
    {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}
);

/**
 * state of a tag in the shown tags menu: all|some|none
 */
const getMenuTagState = (page, tagName) => page.evaluate((isShownFn, tagName) => {
    const isShown = eval(`(${isShownFn})`);
    const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
    const item = menu && Array.from(menu.querySelectorAll('.tw-tags-menu-tag'))
        .find((el) => el.querySelector('.tw-tag-text')?.textContent.trim() === tagName);
    if (!item) return null;
    return {
        state: ['all', 'some', 'none'].find((state) => item.classList.contains(`tw-tags-menu-tag-${state}`)),
        count: item.querySelector('.tw-tag-count')?.textContent.trim() || '',
        pinned: item.classList.contains('tw-tags-menu-pinned'),
    };
}, isShown.toString(), tagName);

const waitForMenuTagState = async (page, tagName, state) => {
    try {
        await page.waitForFunction((isShownFn, tagName, state) => {
            const isShown = eval(`(${isShownFn})`);
            const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
            const item = menu && Array.from(menu.querySelectorAll('.tw-tags-menu-tag'))
                .find((el) => el.querySelector('.tw-tag-text')?.textContent.trim() === tagName);
            return item?.classList.contains(`tw-tags-menu-tag-${state}`);
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, isShown.toString(), tagName, state);
    } catch (err) {
        const error = new Error(`waitForMenuTagState: tag "${tagName}" not in state "${state}" (${JSON.stringify(await getMenuTagState(page, tagName))})`);
        error.cause = err;
        throw error;
    }
};

const clickMenuTag = async (page, tagName) => {
    await waitForTagsMenu(page);
    await page.evaluate((isShownFn, tagName) => {
        const isShown = eval(`(${isShownFn})`);
        const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
        const label = Array.from(menu.querySelectorAll('.tw-tags-menu-tag .tw-tag-text'))
            .find((el) => el.textContent.trim() === tagName);
        label.closest('.tw-tags-menu-tag').setAttribute('data-e2e-tag', '1');
    }, isShown.toString(), tagName);
    await lib.clickOnElement(page, '[data-e2e-tag="1"] .tw-tag');
    await page.evaluate(() => document.querySelectorAll('[data-e2e-tag]').forEach((el) => el.removeAttribute('data-e2e-tag')));
};

/**
 * open context menu of a row and its tags sub menu
 */
const openRowTagsMenu = async (page, rowSelector, cellSelector = '.x-grid3-cell-inner') => {
    await lib.clickOnElement(page, `${rowSelector} ${cellSelector}`, {button: 'right'});
    // NOTE: the text of the item contains the tag icons (a click toggles the tag) -> click on the item icon
    await lib.clickOnElement(page, '.tw-tags-menu-item .x-menu-item-icon');
    await waitForTagsMenu(page);
};

const closeMenus = async (page) => {
    await page.keyboard.press('Escape');
    await page.evaluate(() => Ext.menu.MenuMgr.hideAll());
    await waitForMenusHidden(page);
};

beforeAll(async () => {
    await lib.getBrowser('Admin', 'Gemeinsame Tags');
    await deleteTestData(global.page);
    data = await createTestData(global.page);

    originalPins = await getPins(global.page);
    originalRecents = await getPins(global.page, RECENTS_STATE);
    await setPins(global.page, {addressbook: null, sales: null}, RECENTS_STATE);
    const deletedTag = {id: `e2e-deleted-${sfx}`, name: TAG_DELETED, color: '#000000', type: 'shared'};
    await setPins(global.page, {contact: [data.tagA, data.tagB, deletedTag], position: [data.tagA, data.tagB]});
    originalGridStates = await getGridStates(global.page);
    await setGridStates(global.page, {});
    // NOTE: without broadcasthub the usable tags (loaded on boot) don't know the test tags yet
    await global.page.evaluate(() => Tine.Tinebase.data.SyncedCache.get('Tinebase.usableTags').load({force: true}));
});

afterAll(async () => {
    if (global.page) {
        if (originalPins) {
            await setPins(global.page, originalPins).catch(() => {});
        }
        if (originalRecents) {
            await setPins(global.page, originalRecents, RECENTS_STATE).catch(() => {});
        }
        if (originalGridStates) {
            await setGridStates(global.page, originalGridStates).catch(() => {});
        }
        await deleteTestData(global.page);
    }
    await lib.closeBrowser();
});

describe('Admin shared tags', () => {
    const selectContextApp = async (popup, appName) => {
        const appId = await popup.evaluate((appName) => Tine.Tinebase.appMgr.get(appName).id, appName);
        await lib.clickOnElement(popup, `#adminSharedTagsContextPanel [ext\\:tree-node-id="${appId}"] .x-tree-node-anchor`);
        await popup.waitForFunction((appId) => Ext.getCmp('adminSharedTagsContextPanel').getSelectionModel().getSelectedNode()?.id === appId
            && document.querySelector('.admin-tag-context-models input[type=checkbox]'), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, appId);
    };
    const clickContextModel = async (popup, model) => {
        const id = await popup.evaluate((model) => Array.from(document.querySelectorAll('.admin-tag-context-models input[type=checkbox]'))
            .find((cb) => Ext.getCmp(cb.id).modelName === model)?.id, model);
        expect(id).toBeTruthy();
        await lib.clickOnElement(popup, `#${id}`);
    };
    const getCheckedContextModels = (popup) => popup.evaluate(() => Array.from(document.querySelectorAll('.admin-tag-context-models input[type=checkbox]'))
        .filter((cb) => cb.checked).map((cb) => Ext.getCmp(cb.id).modelName));

    test('restrict shared tag to a model', async () => {
        const popup = await lib.getEditDialog('Tag hinzufügen');
        await lib.formFillInputField(popup, {selector: 'input[name=name]', value: TAG_ADMIN});

        await lib.clickOnElementWithLabel(popup, '.x-tab-strip-text', 'Kontexte');
        // only apps with models with tags (incl. legacy models like Crm leads) are shown
        const treeApps = await popup.evaluate(() => {
            const apps = [];
            Ext.getCmp('adminSharedTagsContextPanel').getRootNode().eachChild((node) => { apps.push(node.attributes.appName); });
            return apps;
        });
        expect(treeApps).toEqual(expect.arrayContaining(['Addressbook', 'Sales', 'Crm']));
        expect(treeApps).toEqual(expect.not.arrayContaining(['Admin', 'ActiveSync', 'Tinebase']));

        // select the app in the tree -> its models are offered
        await selectContextApp(popup, 'Sales');
        await clickContextModel(popup, POSITION_MODEL);

        // app of the model is restricted
        await popup.waitForFunction(() => Array.from(document.querySelectorAll('.x-tree-node-el.admin-tag-context-restricted'))
            .some((el) => /^Sales/.test(el.textContent.trim())), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});

        await lib.clickOnElementWaitPageClosed(popup, '.x-btn-text', 'Ok');

        const tag = await global.page.evaluate(async (name) => {
            const tags = await Tine.Tinebase.searchTags({name, grant: 'view'}, {});
            const tag = _.find(tags.results, {name});
            return tag ? Tine.Admin.getTag(tag.id) : null;
        }, TAG_ADMIN);
        expect(tag).not.toBeNull();
        expect(tag.context_models).toEqual([POSITION_MODEL]);

        // tag is found for positions, but not for other models of the app
        const found = await global.page.evaluate(async (name, model) => {
            const search = async (filter) => _.map((await Tine.Tinebase.searchTags(Object.assign({name, grant: 'use'}, filter), {})).results, 'name');
            return {
                position: await search({application: 'Sales', model}),
                product: await search({application: 'Sales', model: 'Sales_Model_Product'}),
            };
        }, TAG_ADMIN, POSITION_MODEL);
        expect(found.position).toEqual([TAG_ADMIN]);
        expect(found.product).toEqual([]);
    });

    test('model restriction is shown when editing the tag', async () => {
        await lib.refreshGridAndWaitForReady(global.page, '.t-app-admin').catch(() => {});
        const popup = await lib.getEditDialog(TAG_ADMIN, global.page, {selector: '.x-grid3-row .x-grid3-cell-inner', clickCount: 2});
        await lib.clickOnElementWithLabel(popup, '.x-tab-strip-text', 'Kontexte');
        await selectContextApp(popup, 'Sales');
        expect(await getCheckedContextModels(popup)).toEqual([POSITION_MODEL]);
        // only models of the selected app are offered
        expect(await popup.evaluate(() => Array.from(document.querySelectorAll('.admin-tag-context-models input[type=checkbox]'))
            .every((cb) => Ext.getCmp(cb.id).modelName.startsWith('Sales_')))).toBe(true);
        await lib.clickOnElementWaitPageClosed(popup, '.x-btn-text', 'Abbrechen');
    });
});

describe('Tags menu in main screen grid (saved directly)', () => {
    // NOTE: rows get re-rendered when records are updated -> resolve them on each use
    const rowA = () => getRowSelector(global.page, `${sfx}, ${CONTACT_A}`);
    const rowB = () => getRowSelector(global.page, `${sfx}, ${CONTACT_B}`);
    const isSelected = async (row) => global.page.evaluate((sel) => document.querySelector(sel).classList.contains('x-grid3-row-selected'), await row());

    beforeAll(async () => {
        await openApp(global.page, 'Adressbuch', 'Kontakte');
        await quickSearch(global.page, '.t-app-addressbook', sfx);
    });

    afterEach(async () => {
        await closeMenus(global.page).catch(() => {});
    });

    test('tags are shown as tag icons in the grid', async () => {
        await global.page.waitForSelector(`${await rowA()} .x-grid3-td-tags .tw-tagfan .tw-tag-icon`, {visible: true});
        expect(await global.page.$(`${await rowB()} .x-grid3-td-tags .tw-tag-icon`)).toBeNull();
    });

    const selectBoth = async () => {
        await lib.clickOnElement(global.page, `${await rowA()} .x-grid3-cell-inner`);
        await global.page.keyboard.down('Control');
        await lib.clickOnElement(global.page, `${await rowB()} .x-grid3-cell-inner`);
        await global.page.keyboard.up('Control');
        expect(await isSelected(rowA)).toBe(true);
        expect(await isSelected(rowB)).toBe(true);
    };

    test('context menu shows pinned tags with state of the selection', async () => {
        await selectBoth();

        await openRowTagsMenu(global.page, await rowB());
        expect(await getMenuTagState(global.page, TAG_A)).toEqual({state: 'some', count: '1/2', pinned: true});
        expect(await getMenuTagState(global.page, TAG_B)).toEqual({state: 'none', count: '', pinned: true});
        // pins of deleted tags are not shown
        expect(await getMenuTagState(global.page, TAG_DELETED)).toBeNull();
    });

    test('click on tag attaches it to all selected records', async () => {
        await selectBoth();
        await openRowTagsMenu(global.page, await rowB());
        await clickMenuTag(global.page, TAG_A);
        await waitForMenuTagState(global.page, TAG_A, 'all');

        expect(await getContactTags(global.page)).toEqual([[TAG_A], [TAG_A]]);
        // row is updated, selection is kept
        await global.page.waitForSelector(`${await rowB()} .x-grid3-td-tags .tw-tag-icon`, {visible: true});
        expect(await isSelected(rowA)).toBe(true);
        expect(await isSelected(rowB)).toBe(true);
    });

    test('click on tags cell opens tags menu for the selection', async () => {
        await selectBoth();
        await lib.clickOnElement(global.page, `${await rowA()} .x-grid3-td-tags`);
        await waitForTagsMenu(global.page);
        // selection is kept
        expect(await isSelected(rowB)).toBe(true);

        await waitForMenuTagState(global.page, TAG_A, 'all');
        await clickMenuTag(global.page, TAG_A);
        await waitForMenuTagState(global.page, TAG_A, 'none');
        await clickMenuTag(global.page, TAG_B);
        await waitForMenuTagState(global.page, TAG_B, 'all');

        expect(await getContactTags(global.page)).toEqual([[TAG_B], [TAG_B]]);
    });

    /**
     * tag icons in the "Tags" row of the context menu: tagId => all|some|none
     */
    const getRowIconStates = () => global.page.evaluate((isShownFn) => {
        const isShown = eval(`(${isShownFn})`);
        const item = Array.from(document.querySelectorAll('.tw-tags-menu-item')).find(isShown);
        return _.fromPairs(Array.from(item?.querySelectorAll('.tw-tags-menu-item-pin') || []).map((el) => [
            el.getAttribute('data-tag-id'),
            ['full', 'part', 'none'].find((state) => el.classList.contains(`tw-tags-menu-item-pin-${state}`)),
        ]));
    }, isShown.toString());

    // NOTE: rows are re-rendered on update -> find row by text
    const waitForRowTagCount = (text, count) => global.page.waitForFunction((text, count) => {
        const row = Array.from(document.querySelectorAll('.x-grid3-row')).find((el) => el.offsetParent && el.textContent.includes(text));
        return row && row.querySelectorAll('.x-grid3-td-tags .tw-tag-icon').length === count;
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, text, count);

    const clickRowIcon = async (rowSelector, tagId) => {
        await lib.clickOnElement(global.page, `${rowSelector} .x-grid3-cell-inner`, {button: 'right'});
        await global.page.waitForFunction((isShownFn, tagId) => {
            const isShown = eval(`(${isShownFn})`);
            const item = Array.from(document.querySelectorAll('.tw-tags-menu-item')).find(isShown);
            return !item?.classList.contains('tw-tags-menu-item-loading')
                && item?.querySelector(`.tw-tags-menu-item-pin[data-tag-id="${tagId}"]`);
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, isShown.toString(), tagId);
        const states = await getRowIconStates();
        await global.page.evaluate((isShownFn, tagId) => {
            const isShown = eval(`(${isShownFn})`);
            Array.from(document.querySelectorAll('.tw-tags-menu-item')).find(isShown)
                .querySelector(`.tw-tags-menu-item-pin[data-tag-id="${tagId}"]`).setAttribute('data-e2e-icon', '1');
        }, isShown.toString(), tagId);
        await lib.clickOnElement(global.page, '[data-e2e-icon="1"]');
        await waitForMenusHidden(global.page);
        return states;
    };

    test('context menu row shows tags as icons, a click toggles the tag', async () => {
        await lib.clickOnElement(global.page, `${await rowA()} .x-grid3-cell-inner`);

        // attached tag in full color, pinned and not attached tag dimmed
        const states = await clickRowIcon(await rowA(), data.tagA.id);
        expect(states).toEqual({[data.tagA.id]: 'none', [data.tagB.id]: 'full'});
        await waitForRowTagCount(`${sfx}, ${CONTACT_A}`, 2);
        expect(await getContactTags(global.page)).toEqual([[TAG_A, TAG_B].sort(), [TAG_B]]);

        expect(await clickRowIcon(await rowA(), data.tagA.id)).toEqual({[data.tagA.id]: 'full', [data.tagB.id]: 'full'});
        await waitForRowTagCount(`${sfx}, ${CONTACT_A}`, 1);
        expect(await getContactTags(global.page)).toEqual([[TAG_B], [TAG_B]]);
    });

    test('pinning a tag', async () => {
        await openRowTagsMenu(global.page, await rowA());
        await global.page.evaluate((isShownFn, tagName) => {
            const isShown = eval(`(${isShownFn})`);
            const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
            const label = Array.from(menu.querySelectorAll('.tw-tags-menu-tag .tw-tag-text')).find((el) => el.textContent.trim() === tagName);
            label.closest('.tw-tags-menu-tag').setAttribute('data-e2e-pin', '1');
        }, isShown.toString(), TAG_A);
        await lib.clickOnElement(global.page, '[data-e2e-pin="1"] .tw-tags-menu-pin');
        await global.page.waitForFunction((isShownFn, tagName) => {
            const isShown = eval(`(${isShownFn})`);
            const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
            const item = menu && Array.from(menu.querySelectorAll('.tw-tags-menu-tag'))
                .find((el) => el.querySelector('.tw-tag-text')?.textContent.trim() === tagName);
            return item && !item.classList.contains('tw-tags-menu-pinned');
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, isShown.toString(), TAG_A);

        const pins = await global.page.evaluate((id) => _.map(Ext.state.Manager.get(id), 'name'), PINS_STATE.contact);
        expect(pins).toEqual([TAG_B]);
        // tags are not changed by pinning
        expect(await getContactTags(global.page)).toEqual([[TAG_B], [TAG_B]]);
    });

    test('grid is reloaded after detaching tags via dialog', async () => {
        await lib.clickOnElement(global.page, `${await rowA()} .x-grid3-cell-inner`);
        await openRowTagsMenu(global.page, await rowA());
        await lib.clickOnElementWithLabel(global.page, '.tw-tags-menu .x-menu-item-text', 'Tag(s) entfernen…');

        const item = await global.page.waitForFunction((tagName) => Array.from(document.querySelectorAll('.tw-tagtogglebox .tw-tag-combo-item'))
            .find((el) => el.offsetParent && el.textContent.includes(tagName)), {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, TAG_B);
        await (await item.asElement().$('input.tagcheckel')).click();
        await lib.clickOnElementWithLabel(global.page, '.x-window button', 'Tags entfernen');

        await waitForRowTagCount(`${sfx}, ${CONTACT_A}`, 0);
        expect(await getContactTags(global.page)).toEqual([[], [TAG_B]]);
    });
});

describe('Tags cache (broadcasthub)', () => {
    beforeAll(async () => {
        await openApp(global.page, 'Adressbuch', 'Kontakte');
        await quickSearch(global.page, '.t-app-addressbook', sfx);
    });

    test('tag deleted elsewhere disappears from the open client', async () => {
        const hubActive = await global.page.evaluate(() => !!Tine.Tinebase.configManager.get('broadcasthub')?.active);
        if (!hubActive) {
            console.info('broadcasthub not active -> skipped');
            return;
        }
        const TAG_C = `${sfx} gleich weg`;
        const tagC = await global.page.evaluate(async (name) => {
            const tag = await Tine.Admin.saveTag({name, color: '#7a1fa2', contexts: ['any'],
                rights: [{account_id: 0, account_type: 'anyone', view_right: true, use_right: true}]});
            return _.pick(tag, ['id', 'name', 'color', 'type', 'description']);
        }, TAG_C);
        const pins = await getPins(global.page);
        await setPins(global.page, {...pins, contact: (pins.contact || []).concat(tagC)});

        // new tag is synced to the client without reload
        const rowA = await getRowSelector(global.page, `${sfx}, ${CONTACT_A}`);
        await lib.clickOnElement(global.page, `${rowA} .x-grid3-cell-inner`);
        await global.page.waitForFunction((name) => (Tine.Tinebase.data.SyncedCache.get('Tinebase.usableTags').getData() || [])
            .some((tag) => tag.name === name), {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, TAG_C);
        await openRowTagsMenu(global.page, rowA);
        expect(await getMenuTagState(global.page, TAG_C)).toEqual({state: 'none', count: '', pinned: true});

        // deleted elsewhere -> open menu is updated
        await global.page.evaluate(async (id) => Tine.Admin.deleteTags([id]), tagC.id);
        await global.page.waitForFunction((isShownFn, tagName) => {
            const isShown = eval(`(${isShownFn})`);
            const menu = Array.from(document.querySelectorAll('.tw-tags-menu')).find(isShown);
            return menu && !Array.from(menu.querySelectorAll('.tw-tag-text')).some((el) => el.textContent.trim() === tagName);
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, isShown.toString(), TAG_C);
        await closeMenus(global.page);
    });
});

// GDPR: contact dialog might ask for the data provenance
const PROVENANCE_TITLE = 'Bitte die Datenherkunft bestätigen';
const saveContact = async (dialog) => {
    const closed = new Promise((resolve) => dialog.once('close', resolve));
    await lib.clickOnElementWithLabel(dialog, '.x-toolbar-right-row button', 'Ok');

    const provenance = dialog.waitForFunction((title) => Array.from(document.querySelectorAll('.x-window-header-text'))
        .some((el) => el.textContent.trim() === title && el.offsetParent),
    {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, PROVENANCE_TITLE).then(() => true, () => false);
    if (await Promise.race([closed.then(() => false), provenance])) {
        for (const win of await dialog.$$('.x-window')) {
            const isProvenance = await win.evaluate((el, title) => el.querySelector('.x-window-header-text')?.textContent.trim() === title
                && !!el.offsetParent, PROVENANCE_TITLE);
            if (isProvenance) {
                const [okBtn] = await win.$$('xpath/.//button[normalize-space(.)="Ok"]');
                await okBtn.click();
            }
        }
    }

    await closed;
};

describe('TagsPanel in edit dialog', () => {
    test('tags are shown as labels, pinned tags can be added', async () => {
        // contact has tag B, tags A and B are pinned
        await global.page.evaluate(async (id, tag) => {
            const contact = await Tine.Addressbook.getContact(id);
            await Tine.Addressbook.saveContact(Object.assign(contact, {tags: [tag]}));
        }, data.contactIds[0], data.tagB);
        await setPins(global.page, {contact: [data.tagA, data.tagB], position: [data.tagA, data.tagB]});
        // NOTE: reloads the grid, there might be many contacts (paging)
        await quickSearch(global.page, '.t-app-addressbook', sfx);
        const popup = await lib.getEditDialog(`${sfx}, ${CONTACT_A}`, global.page, {selector: '.x-grid3-row .x-grid3-cell-inner', clickCount: 2});

        await popup.waitForFunction((tagName) => Array.from(document.querySelectorAll('.tw-tag-item .tw-tag-text'))
            .some((el) => el.textContent.trim() === tagName), {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, TAG_B);

        // pinned, not attached tag is offered as ghost tag
        await lib.clickOnElementWithLabel(popup, '.tw-tagspanel-pinned .tw-tag-ghost', `+ ${TAG_A}`);
        await popup.waitForFunction((tagName) => Array.from(document.querySelectorAll('.tw-tag-item .tw-tag-text'))
            .some((el) => el.textContent.trim() === tagName), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, TAG_A);
        expect(await popup.$$eval('.tw-tagspanel-pinned .tw-tag-ghost', (els) => els.length)).toBe(0);

        await saveContact(popup);
        expect((await getContactTags(global.page))[0]).toEqual([TAG_A, TAG_B].sort());
    });

    afterAll(async () => {
        await quickSearch(global.page, '.t-app-addressbook', '').catch(() => {});
    });
});

describe('Tags menu in record field (saved with the main record)', () => {
    let popup = null;
    const gridSelector = '.x-grid-panel.x-grid3-quickadd';

    beforeAll(async () => {
        await openApp(global.page, 'Sales', 'Angebote');
        popup = await lib.getEditDialog(data.offerNumber, global.page, {selector: '.x-grid3-row .x-grid3-cell-inner', clickCount: 2});
    });

    test('context menu of position sets tags locally', async () => {
        await openRowTagsMenu(popup, await getRowSelector(popup, POS_A, gridSelector), '.x-grid3-td-title');
        expect(await getMenuTagState(popup, TAG_A)).toEqual({state: 'none', count: '', pinned: true});
        await clickMenuTag(popup, TAG_A);
        await waitForMenuTagState(popup, TAG_A, 'all');
        await closeMenus(popup);

        await popup.waitForSelector(`${await getRowSelector(popup, POS_A, gridSelector)} .x-grid3-td-tags .tw-tag-icon`, {visible: true});
        // not saved yet
        expect(await getPositionTags(global.page)).toEqual({[POS_A]: [], [POS_B]: []});
        // row with tag is tinted, following rows without tags (and quickadd row) are not
        expect(await getRowTintColors(POS_A)).toBe(1);
        expect(await getRowTintColors(POS_B)).toBe(0);
        expect(await getRowTintColors(null)).toBe(0);
    });

    test('click on tags cell of position opens tags menu', async () => {
        await lib.clickOnElement(popup, `${await getRowSelector(popup, POS_B, gridSelector)} .x-grid3-td-tags`);
        await waitForTagsMenu(popup);
        await clickMenuTag(popup, TAG_B);
        await waitForMenuTagState(popup, TAG_B, 'all');
        await closeMenus(popup);
        await popup.waitForSelector(`${await getRowSelector(popup, POS_B, gridSelector)} .x-grid3-td-tags .tw-tag-icon`, {visible: true});
    });

    /**
     * row tint (TagsRowTintPlugin): number of colors of the row with the given text, or of the quickadd row
     */
    const getRowTintColors = (text) => popup.evaluate((gridSelector, text) => {
        const grid = Ext.getCmp(document.querySelector(gridSelector).id);
        const row = text ? Array.from(grid.getView().getRows()).find((el) => el.textContent.includes(text))
            : grid.getView().getRow(grid.store.indexOf(grid.quickaddRecord));
        const tint = row.style.getPropertyValue('--tw-row-tint');
        return row.classList.contains('tw-tags-row-tint') ? new Set(tint.match(/color-mix\([^)]*\)/g) || []).size : 0;
    }, gridSelector, text);

    test('rows are tinted with the colors of their tags', async () => {
        expect(await getRowTintColors(POS_A)).toBe(1);
        expect(await getRowTintColors(POS_B)).toBe(1);
        expect(await getRowTintColors(null)).toBe(0);

        // n tags -> n colors
        await lib.clickOnElement(popup, `${await getRowSelector(popup, POS_B, gridSelector)} .x-grid3-td-tags`);
        await clickMenuTag(popup, TAG_A);
        await waitForMenuTagState(popup, TAG_A, 'all');
        await closeMenus(popup);
        expect(await getRowTintColors(POS_B)).toBe(2);

        await lib.clickOnElement(popup, `${await getRowSelector(popup, POS_B, gridSelector)} .x-grid3-td-tags`);
        await clickMenuTag(popup, TAG_A);
        await waitForMenuTagState(popup, TAG_A, 'none');
        await closeMenus(popup);
        expect(await getRowTintColors(POS_B)).toBe(1);
    });

    test('tags of positions are saved with the document', async () => {
        await lib.clickOnElementWaitPageClosed(popup, '.x-btn-text', 'Ok');
        expect(await getPositionTags(global.page)).toEqual({[POS_A]: [TAG_A], [POS_B]: [TAG_B]});
    });
});
