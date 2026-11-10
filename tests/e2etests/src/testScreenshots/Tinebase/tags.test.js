import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

// screenshots of the user manual for tags (docs/users/manual/ca_StandardBedienhinweise.md#tags, ha_Sales.md#positionen)
// - tags column of the addressbook grid
// - tags in the context menu (tags row and tags menu)
// - "add tags" dialog with several tags
// - tagged and tinted positions of an offer
//
// tags, pins and the offer are created / set for the screenshots and removed / restored afterwards,
// demo contacts get the tags temporarily

const TAGS = [
    {name: 'Prüfen', color: '#e9b200', description: 'Datensatz muss noch geprüft werden'},
    {name: 'Rückruf', color: '#2f7de1', description: 'Rückruf vereinbart'},
    {name: 'VIP', color: '#c0392b', description: 'Besonders wichtiger Kontakt'},
];
const CUSTOMER = 'Beispiel GmbH (Handbuch)';

const PINS_STATE = {
    contact: 'tinebase-tags-pins-addressbook-contact',
    position: 'tinebase-tags-pins-sales-documentposition_offer',
    addressbookRecents: 'tinebase-tag-addressbook-recents',
    salesRecents: 'tinebase-tag-sales-recents',
};

let tags = null;
let contacts = null;
let offerNumber = null;
let originalState = null;

const deleteTestData = (page) => page.evaluate(async (TAGS, CUSTOMER) => {
    const customers = await Tine.Sales.searchCustomers([{field: 'name', operator: 'equals', value: CUSTOMER}], {});
    if (customers.results.length) {
        const offers = await Tine.Sales.searchDocument_Offers([{field: 'customer_id', operator: 'definedBy', value: [
            {field: 'name', operator: 'equals', value: CUSTOMER}]}], {});
        if (offers.results.length) await Tine.Sales.deleteDocument_Offers(_.map(offers.results, 'id'));
        await Tine.Sales.deleteCustomers(_.map(customers.results, 'id'));
    }
    // only the tags of this test (name and description)
    const found = await Tine.Tinebase.searchTags({grant: 'view'}, {});
    const ids = _.map(_.filter(found.results, (tag) => _.find(TAGS, {name: tag.name, description: tag.description})), 'id');
    if (ids.length) await Tine.Admin.deleteTags(ids);
}, TAGS, CUSTOMER);

const getState = (page) => page.evaluate((ids) => _.mapValues(ids, (id) => Ext.state.Manager.get(id) || null), PINS_STATE);

const setState = (page, values) => page.evaluate(async (ids, values) => {
    const provider = Ext.state.Manager.getProvider();
    for (const [key, id] of Object.entries(ids)) {
        if (values[key]) {
            await provider.set(id, values[key]);
        } else {
            await provider.clear(id);
        }
    }
}, PINS_STATE, values);

/**
 * tags of the contacts in grid rows 1 - 4 (row 0 might be the contact of the current user):
 * [Prüfen], [Prüfen], [Prüfen, Rückruf], [VIP] -> rows 1 - 3 selected: Prüfen all, Rückruf some, VIP none
 * @return original tags of the contacts
 */
const tagContacts = (page, tags) => page.evaluate(async (tags) => {
    const grid = Ext.getCmp(document.querySelector('.t-app-addressbook .x-grid-panel').id);
    const ids = grid.store.getRange(1, 4).map((record) => record.id);
    const contactTags = [[tags[0]], [tags[0]], [tags[0], tags[1]], [tags[2]]];
    const result = [];
    for (const [idx, id] of ids.entries()) {
        const contact = await Tine.Addressbook.getContact(id);
        result.push({id, name: contact.n_fileas, tags: contact.tags || []});
        await Tine.Addressbook.saveContact(Object.assign(contact, {tags: contactTags[idx].concat(contact.tags || [])}));
    }
    return result;
}, tags);

const restoreContacts = (page) => page.evaluate(async (contacts) => {
    for (const {id, tags} of contacts) {
        const contact = await Tine.Addressbook.getContact(id);
        await Tine.Addressbook.saveContact(Object.assign(contact, {tags}));
    }
}, contacts);

const rowSelector = (idx) => `.t-app-addressbook .x-grid3-body .x-grid3-row:nth-child(${idx + 1})`;

const isShown = (el) => {
    const style = getComputedStyle(el);
    const rect = el.getBoundingClientRect();
    return style.visibility !== 'hidden' && style.display !== 'none' && rect.width > 0 && rect.right > 0 && rect.bottom > 0;
};

const waitForTagsMenu = (page) => page.waitForFunction(
    `Array.from(document.querySelectorAll('.tw-tags-menu')).some((el) => (${isShown.toString()})(el) && !el.classList.contains('tw-tags-menu-loading'))`,
    {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}
);

const closeMenus = async (page) => {
    await page.keyboard.press('Escape');
    await page.evaluate(() => Ext.menu.MenuMgr.hideAll());
};

const openApp = async (page, app, module) => {
    await lib.openAppMenu(page);
    await expectPuppeteer(page).toClick('div.application-menu-item__text', {text: app});
    await lib.closeModalDialogs(page);
    await lib.waitForAppReady(page);
    await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west .x-tree-node a span', {text: module});
    await lib.waitForAppReady(page);
};

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
    await deleteTestData(page);

    tags = await page.evaluate(async (TAGS) => {
        const rights = [{account_id: 0, account_type: 'anyone', view_right: true, use_right: true}];
        const result = [];
        for (const tag of TAGS) {
            const saved = await Tine.Admin.saveTag(Object.assign({contexts: ['any'], rights}, tag));
            result.push(_.pick(saved, ['id', 'name', 'color', 'type', 'description']));
        }
        return result;
    }, TAGS);

    originalState = await getState(page);
    await setState(page, {contact: tags, position: tags});

    await page.waitForSelector(`${rowSelector(4)}`, {visible: true});
    contacts = await tagContacts(page, tags);
    await lib.refreshGridAndWaitForReady(page, '.t-app-addressbook');
    await page.waitForSelector(`${rowSelector(3)} .x-grid3-td-tags .tw-tag-icon`, {visible: true});
});

afterAll(async () => {
    if (page) {
        if (contacts) await restoreContacts(page).catch(() => {});
        if (originalState) await setState(page, originalState).catch(() => {});
        await deleteTestData(page).catch(() => {});
    }
    await lib.closeBrowser();
});

describe('tags in grids', () => {
    test('tags column', async () => {
        await page.mouse.move(0, 0);
        await page.hover(`${rowSelector(3)} .x-grid3-td-tags .tw-tagfan`);
        await page.waitForFunction((name) => Array.from(document.querySelectorAll('.x-tip'))
            .some((tip) => getComputedStyle(tip).visibility !== 'hidden' && getComputedStyle(tip).display !== 'none' && tip.textContent.includes(name)), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, TAGS[1].name);
        await lib.makeScreenshot(page, {path: 'screenshots/StandardBedienhinweise/111_standardbedienhinweise_tags_tabelle.png'});
        await page.mouse.move(0, 0);
    });

    test('tags row of the context menu', async () => {
        await lib.clickOnElement(page, `${rowSelector(1)} .x-grid3-col-n_fileas`);
        await page.keyboard.down('Shift');
        await lib.clickOnElement(page, `${rowSelector(3)} .x-grid3-col-n_fileas`);
        await page.keyboard.up('Shift');
        await lib.clickOnElement(page, `${rowSelector(2)} .x-grid3-col-n_fileas`, {button: 'right'});
        await page.waitForSelector('.tw-tags-menu-item .tw-tags-menu-item-pin', {visible: true});
        await page.hover('.tw-tags-menu-item .tw-tags-menu-item-pin');
        await lib.makeScreenshot(page, {path: 'screenshots/Adressbuch/18_adressbuch_kontakten_tags_zuweisen.png'});
    });

    test('tags menu', async () => {
        // NOTE: the text of the item contains the tag icons (a click toggles the tag) -> click on the item icon
        await lib.clickOnElement(page, '.tw-tags-menu-item .x-menu-item-icon');
        await waitForTagsMenu(page);
        // no tooltip of the tags row above the tags menu
        await page.hover('.tw-tags-menu .tw-tags-menu-tag .x-menu-item-icon');
        await page.waitForFunction(() => !Array.from(document.querySelectorAll('.x-tip')).some((tip) => getComputedStyle(tip).visibility !== 'hidden' && getComputedStyle(tip).display !== 'none'),
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
        await lib.makeScreenshot(page, {path: 'screenshots/StandardBedienhinweise/112_standardbedienhinweise_tags_kontextmenue.png'});
    });

    test('add tags dialog', async () => {
        await lib.clickOnElementWithLabel(page, '.tw-tags-menu .x-menu-item-text', 'Tags hinzufügen…');
        await page.waitForSelector('.x-window .x-form-arrow-trigger', {visible: true});
        for (const tag of [tags[1], tags[2]]) {
            // NOTE: the combo is reset (deferred) after a selection, which might clear the typed text -> type again
            const deadline = Date.now() + lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE');
            while (true) {
                await lib.clickOnElement(page, '.x-window input.x-form-text', {clickCount: 3});
                await page.keyboard.press('Backspace');
                await page.keyboard.type(tag.name, {delay: 50});
                try {
                    await lib.clickOnElementWithLabel(page, '.tw-tag-combo-item .tw-tag-text', tag.name, {timeout: 2000});
                    break;
                } catch (err) {
                    if (Date.now() > deadline) throw err;
                    // list might be collapsed -> expand it
                    await page.keyboard.press('ArrowDown');
                    try {
                        await lib.clickOnElementWithLabel(page, '.tw-tag-combo-item .tw-tag-text', tag.name, {timeout: 2000});
                        break;
                    } catch (e) {
                        // type again
                    }
                }
            }
            await page.waitForFunction((name) => Array.from(document.querySelectorAll('.x-window .x-grid3-row'))
                .some((row) => row.textContent.includes(name)), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, tag.name);
        }
        await lib.makeScreenshot(page, {path: 'screenshots/StandardBedienhinweise/113_standardbedienhinweise_tags_hinzufuegen.png'});
        await lib.clickOnElementWithLabel(page, '.x-window .x-btn-text', 'Abbrechen');
        await closeMenus(page);
    });
});

describe('tags of sales positions', () => {
    let popup = null;

    beforeAll(async () => {
        offerNumber = await page.evaluate(async (CUSTOMER, tags) => {
            const divisionId = (await Tine.Sales.searchDivisions([], {})).results[0].id;
            const customer = await Tine.Sales.saveCustomer({name: CUSTOMER, debitors: [{name: CUSTOMER, division_id: divisionId}]});
            const offer = await Tine.Sales.saveDocument_Offer({
                customer_id: customer,
                division_id: divisionId,
                offer_status: 'DRAFT',
                positions: [
                    {title: 'Beratung und Konzeption', type: 'TEXT', sorting: 10000, tags: [tags[0]]},
                    {title: 'Installation vor Ort', type: 'TEXT', sorting: 20000, tags: [tags[0], tags[2]]},
                    {title: 'Schulung der Mitarbeiter*innen', type: 'TEXT', sorting: 30000},
                    {title: 'Wartung (12 Monate)', type: 'TEXT', sorting: 40000, tags: [tags[1]]},
                ]
            });
            return offer.document_number;
        }, CUSTOMER, tags);
        await openApp(page, 'Sales', 'Angebote');
        popup = await lib.getEditDialog(offerNumber, page, {selector: '.x-grid3-row .x-grid3-cell-inner', clickCount: 2});
    });

    afterAll(async () => {
        if (popup && !popup.isClosed()) await popup.close();
    });

    test('tagged and tinted positions', async () => {
        await popup.waitForSelector('.x-grid3-row.tw-tags-row-tint', {visible: true});
        await lib.makeScreenshot(popup, {path: 'screenshots/Sales/7_sales_angebot_positionen_tags.png'});
    });
});
