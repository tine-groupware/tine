import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

/**
 * E2E tests for CrewScheduling (Dienstplanung)
 *
 * The tests create their own demo data (contacts, lists, scheduling roles, event type and events)
 * via API and remove it again afterwards. Data left over from aborted runs is removed before.
 *
 * Data:
 *   role A: Anna, Bert       role B: Bert, Cora
 *   event 1 + 2: event type requiring one attendee of role A and one of role B
 */

const PREFIX = 'E2E-CS';
const sfx = `${PREFIX}-${Math.round(Math.random() * 10000000)}`;

// NOTE: main screens of apps opened before stay in the DOM (hidden), so generic selectors must be scoped to the app
const APP = '.t-app-crewscheduling';
const GRID = `${APP} .cs-event-member-grid`;
const PANEL = `${APP} .cs-member-selection-panel`;
const FILTER_FIELD = `${APP} input.cs-member-filter`;
const BUTTON = `${APP} .x-btn-text`;

let data = null;

/**
 * removes all demo data of this test (also from aborted runs)
 * NOTE: runs in page context
 */
const deleteDemoData = async (prefix) => {
    const ids = (response) => (response?.results || []).map((r) => r.id);
    const period = {from: `${new Date().getFullYear() - 1}-01-01 00:00:00`, until: `${new Date().getFullYear() + 2}-01-01 00:00:00`};

    const events = ids(await Tine.Calendar.searchEvents([
        {field: 'query', operator: 'contains', value: prefix},
        {field: 'period', operator: 'within', value: period}
    ], {}));
    if (events.length) await Tine.Calendar.deleteEvents(events);

    const types = ids(await Tine.Calendar.searchEventTypes([{field: 'name', operator: 'startswith', value: prefix}], {}));
    if (types.length) await Tine.Calendar.deleteEventTypes(types);

    const roles = ids(await Tine.CrewScheduling.searchSchedulingRoles([{field: 'name', operator: 'startswith', value: prefix}], {}));
    if (roles.length) await Tine.CrewScheduling.deleteSchedulingRoles(roles);

    const lists = ids(await Tine.Addressbook.searchLists([{field: 'name', operator: 'startswith', value: prefix}], {}));
    if (lists.length) await Tine.Addressbook.deleteLists(lists);

    const contacts = ids(await Tine.Addressbook.searchContacts([{field: 'n_given', operator: 'startswith', value: prefix}], {}));
    if (contacts.length) await Tine.Addressbook.deleteContacts(contacts);
};

/**
 * creates demo data
 * NOTE: runs in page context
 */
const createDemoData = async (sfx) => {
    const addressbookId = Tine.Addressbook.registry.get('defaultAddressbook').id;
    const calendarId = Tine.Calendar.registry.get('defaultContainer').id;

    const saveContact = (name) => Tine.Addressbook.saveContact({n_given: sfx, n_family: name, container_id: addressbookId});
    const [anna, bert, cora] = await Promise.all([saveContact('Alpha Anna'), saveContact('Beta Bert'), saveContact('Gamma Cora')]);

    const saveList = (name, members) => Tine.Addressbook.saveList({name: `${sfx} ${name}`, type: 'list', members: members.map((c) => c.id), container_id: addressbookId});
    const listA = await saveList('Gruppe A', [anna, bert]);
    const listB = await saveList('Gruppe B', [bert, cora]);

    // NOTE: keys must be unique, column ids get matched against them
    const keyPrefix = String.fromCharCode(...[0, 0].map(() => 65 + Math.floor(Math.random() * 26)));
    const saveRole = (name, order, list) => Tine.CrewScheduling.saveSchedulingRole({
        key: `${keyPrefix}${name}`,
        name: `${sfx} Rolle ${name}`,
        order,
        role_attendee_required_groups: [{group: list.id}],
        role_attendee_required_groups_operator: 'OR'
    });
    const roleA = await saveRole('A', 1, listA);
    const roleB = await saveRole('B', 2, listB);

    const eventType = await Tine.Calendar.saveEventType({
        short_name: keyPrefix,
        name: `${sfx} Typ`,
        cs_role_configs: [roleA, roleB].map((role) => ({scheduling_role: role.id, num_required_role_attendee: 1}))
    });

    // NOTE: events must be in the current month (default period of crew scheduling)
    const saveEvent = (name, day) => {
        const dtstart = new Date();
        dtstart.setDate(day);
        dtstart.setHours(10, 0, 0, 0);
        const dtend = new Date(dtstart.getTime() + 3600000);
        return Tine.Calendar.saveEvent({
            summary: `${sfx} ${name}`,
            dtstart: dtstart.format('Y-m-d H:i:s'),
            dtend: dtend.format('Y-m-d H:i:s'),
            container_id: calendarId,
            event_types: [{event_type: eventType.id}]
        });
    };
    // NOTE: the quick search matches each word on its own (AND), so the names must differ in whole words
    //       (e.g. "Messe 2" would also match "E2E-CS-… Messe 1", as the prefix contains a "2")
    const event1 = await saveEvent('Frühmesse', 15);
    const event2 = await saveEvent('Abendmesse', 16);

    const contact = (c) => ({id: c.id, name: c.n_fileas});
    return {
        anna: contact(anna), bert: contact(bert), cora: contact(cora),
        roleA: {id: roleA.id, name: roleA.name}, roleB: {id: roleB.id, name: roleB.name},
        event1: {id: event1.id, summary: event1.summary}, event2: {id: event2.id, summary: event2.summary}
    };
};

/**
 * returns names of all member tokens shown in the member selection panel (only demo data)
 */
const getPanelMembers = async () => global.page.evaluate((panel, sfx) => {
    return Array.from(document.querySelectorAll(`${panel} table.cs-member-token`))
        .map((token) => token.querySelector('.cs-name').textContent.trim())
        .filter((name) => name.includes(sfx))
        .sort();
}, PANEL, sfx);

/**
 * waits until the member selection panel shows exactly the given members
 */
const waitForPanelMembers = async (members) => {
    const expected = members.map((m) => m.name).sort();
    try {
        await global.page.waitForFunction((panel, sfx, expected) => {
            const names = Array.from(document.querySelectorAll(`${panel} table.cs-member-token`))
                .map((token) => token.querySelector('.cs-name').textContent.trim())
                .filter((name) => name.includes(sfx))
                .sort();
            return JSON.stringify(names) === JSON.stringify(expected);
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')}, PANEL, sfx, expected);
    } catch (err) {
        const filter = await global.page.evaluate((sel) => document.querySelector(sel)?.value, FILTER_FIELD);
        throw new Error(`member panel shows [${await getPanelMembers()}], expected [${expected}] (name filter: "${filter}")`, {cause: err});
    }
};

/**
 * returns the counts shown in the member tokens of the member selection panel {name: count}
 */
const getPanelCounts = async () => global.page.evaluate((panel) => {
    return Object.fromEntries(Array.from(document.querySelectorAll(`${panel} table.cs-member-token`)).map((token) => [
        token.querySelector('.cs-name').textContent.trim(),
        token.querySelector('.cs-count').textContent.trim()
    ]));
}, PANEL);

const waitForPanelCount = async (member, count) => {
    try {
        await global.page.waitForFunction((panel, name, count) => {
            const token = Array.from(document.querySelectorAll(`${panel} table.cs-member-token`))
                .find((t) => t.querySelector('.cs-name').textContent.trim() === name);
            return token?.querySelector('.cs-count').textContent.trim() === count;
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, PANEL, member.name, String(count));
    } catch (err) {
        throw new Error(`count of "${member.name}" is "${(await getPanelCounts())[member.name]}", expected "${count}"`, {cause: err});
    }
};

/**
 * returns summaries of the demo events shown in the grid
 * NOTE: runs in page context
 */
const gridEvents = (grid, summaries) => {
    const rows = Array.from(document.querySelectorAll(`${grid} .x-grid3-row`)).filter((row) => row.offsetParent !== null);
    return summaries.filter((summary) => rows.some((row) => row.textContent.includes(summary))).sort();
};

const getGridEvents = async () => global.page.evaluate(gridEvents, GRID, [data.event1.summary, data.event2.summary]);

const waitForGridEvents = async (events) => {
    const expected = events.map((e) => e.summary).sort();
    try {
        await global.page.waitForFunction(`JSON.stringify((${gridEvents})(${JSON.stringify(GRID)}, ${JSON.stringify([data.event1.summary, data.event2.summary])})) === ${JSON.stringify(JSON.stringify(expected))}`,
            {timeout: lib.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')});
    } catch (err) {
        throw new Error(`grid shows [${await getGridEvents()}], expected [${expected}]`, {cause: err});
    }
};

/**
 * selector of the drop zone (members cell) of given event and role
 */
const cellSelector = (event, role) => `${GRID} [id="${event.id}:${role.id}"]`;

const panelTokenHandle = async (member) => {
    const handle = await global.page.waitForFunction((panel, name) => {
        return Array.from(document.querySelectorAll(`${panel} table.cs-member-token`))
            .find((t) => t.querySelector('.cs-name').textContent.trim() === name) || null;
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, PANEL, member.name);
    return handle.asElement();
};

const cellTokenHandle = async (event, role) => {
    const selector = `${cellSelector(event, role)} table.cs-member-token`;
    await global.page.waitForSelector(selector, {visible: true});
    return global.page.$(selector);
};

/**
 * drags the element onto the target element like a user does (Ext.dd needs some mouse moves)
 * NOTE: the elements are looked up again until both are shown - member tokens get rerendered (e.g. after the
 *       selection changed), a handle looked up before can be detached already (no bounding box)
 *
 * @param {Function} getSource - async, returns the element handle to drag
 * @param {Function} getTarget - async, returns the element handle to drop onto
 */
const dragAndDrop = async (getSource, getTarget) => {
    const page = global.page;
    const timeout = lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE');
    const deadline = Date.now() + timeout;
    let source = null;
    let target = null;
    for (;;) {
        source = await (await getSource())?.boundingBox();
        target = await (await getTarget())?.boundingBox();
        if (source && target) break;
        if (Date.now() > deadline) {
            throw new Error(`dragAndDrop: ${source ? 'target' : 'source'} not shown after ${timeout}ms`);
        }
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    const from = {x: source.x + source.width / 2, y: source.y + source.height / 2};
    const to = {x: target.x + target.width / 2, y: target.y + target.height / 2};

    await page.mouse.move(from.x, from.y);
    await page.mouse.down();
    await page.mouse.move(from.x + 10, from.y + 10, {steps: 5});
    await page.mouse.move(to.x, to.y, {steps: 20});
    await page.mouse.up();
};

/**
 * waits until the drag operation is finished (drag proxy is hidden)
 * NOTE: drag proxies are rendered into the body (not into the app), so they can't be scoped to the app.
 *       waitForElement waits until *all* proxies are hidden, i.e. no drag operation is running anywhere.
 * NOTE: possible drop cells of members selected in the member panel stay masked after dropping
 */
const waitForDragFinished = async () => {
    await lib.waitForElement(global.page, '.x-dd-drag-proxy', {hidden: true});
};

/**
 * waits until crew scheduling data is (re)loaded
 */
const waitForCrewSchedulingLoaded = async () => {
    await global.page.waitForSelector(`${GRID} .x-grid3-row`, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    await lib.waitForAppReady(global.page);
};

/**
 * the apply button is enabled when there are unsaved changes
 */
const isApplyButtonDisabled = async () => global.page.evaluate((app) => {
    const btn = Array.from(document.querySelectorAll(`${app} table.x-btn`))
        .find((b) => b.querySelector('.x-btn-text')?.textContent.trim() === 'Ok');
    return btn?.classList.contains('x-item-disabled');
}, APP);

/**
 * saves the changes via Ok button and waits until data is reloaded
 */
const applyChanges = async () => {
    expect(await isApplyButtonDisabled()).toBe(false);
    await lib.clickOnElementWithLabel(global.page, BUTTON, 'Ok');
    await global.page.waitForFunction((app) => {
        const btn = Array.from(document.querySelectorAll(`${app} table.x-btn`))
            .find((b) => b.querySelector('.x-btn-text')?.textContent.trim() === 'Ok');
        return btn?.classList.contains('x-item-disabled');
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, APP);
    await lib.waitForElement(global.page, '.modal.vue-message-box', {hidden: true});
    await waitForCrewSchedulingLoaded();
};

/**
 * returns contact ids of the event attendee with given role (from server)
 */
const getRoleAttendeeIds = async (event, role) => global.page.evaluate(async (eventId, roleId) => {
    const ev = await Tine.Calendar.getEvent(eventId);
    return (ev.attendee || [])
        .filter((a) => (a.crewscheduling_roles || []).some((r) => (r.role?.id || r.role) === roleId))
        .map((a) => a.user_id?.id || a.user_id);
}, event.id, role.id);

/**
 * clears a clearable text field via its clear trigger (x) like a user does
 * NOTE: selecting the text via triple click is not reliable on slow machines
 */
const clearField = async (page, wrapSelector) => {
    await lib.clickOnElement(page, `${wrapSelector} img.x-form-clear-trigger`);
    await page.waitForFunction((sel) => document.querySelector(`${sel} input`)?.value === '',
        {timeout: lib.getEnvInt('TEST_TIMEOUT_FORM_VALUE_CHANGED')}, wrapSelector);
};

const clearMemberFilter = async () => clearField(global.page, `${APP} .x-form-field-trigger-wrap:has(input.cs-member-filter)`);

/**
 * toggles the visibility of a role column via column menu
 * NOTE: menus are rendered into the body (not into the app), so only visible menu items are considered
 */
const toggleRoleColumn = async (role) => {
    await lib.clickOnElement(global.page, `${GRID} .ext-ux-grid-gridviewmenuplugin-menuBtn`);
    const item = await global.page.waitForFunction((name) => {
        return Array.from(document.querySelectorAll('.x-menu-item'))
            .find((el) => el.offsetParent !== null && el.textContent.includes(name)) || null;
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, role.name);
    await item.asElement().click();
    await global.page.keyboard.press('Escape');
    await lib.waitForElement(global.page, '.x-menu', {hidden: true});
};

const isRoleColumnVisible = async (role) => global.page.evaluate((grid, name) => {
    return Array.from(document.querySelectorAll(`${grid} .x-grid3-hd-inner`))
        .some((hd) => hd.offsetParent !== null && hd.textContent.includes(name));
}, GRID, role.name);

beforeAll(async () => {
    await lib.getBrowser();
    const page = global.page;

    await page.evaluate(deleteDemoData, PREFIX);
    data = await page.evaluate(createDemoData, sfx);

    // reload client, so crew scheduling caches (roles, event types, lists) contain demo data
    await page.reload({waitUntil: 'domcontentloaded'});
    await lib.waitForPageHeader(page);
    await lib.closeModalDialogs(page);
    await lib.waitForAppReady(page);

    await lib.openAppMenu(page);
    await expectPuppeteer(page).toClick('div.application-menu-item__text', {text: 'Dienstplanung'});
    await lib.closeModalDialogs(page);
    await waitForCrewSchedulingLoaded();
});

afterAll(async () => {
    try {
        if (global.page) {
            await global.page.evaluate(deleteDemoData, PREFIX);
        }
    } finally {
        await lib.closeBrowser();
    }
});

describe('Crew scheduling', () => {
    test('shows events, roles and members', async () => {
        await waitForGridEvents([data.event1, data.event2]);
        expect(await isRoleColumnVisible(data.roleA)).toBe(true);
        expect(await isRoleColumnVisible(data.roleB)).toBe(true);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
        await global.page.waitForSelector(cellSelector(data.event1, data.roleA), {visible: true});
    });

    test('members are shown after browser reload', async () => {
        await global.page.reload({waitUntil: 'domcontentloaded'});
        await lib.waitForPageHeader(global.page);
        await waitForCrewSchedulingLoaded();

        await waitForGridEvents([data.event1, data.event2]);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
    });

    test('filter members by name', async () => {
        await lib.formFillInputField(global.page, {selector: FILTER_FIELD, value: 'Alpha'});
        await waitForPanelMembers([data.anna]);

        // search terms are or-ed
        await lib.formFillInputField(global.page, {selector: FILTER_FIELD, value: 'Alpha Gamma'});
        await waitForPanelMembers([data.anna, data.cora]);

        await clearMemberFilter();
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
    });

    test('selecting a cell filters members by capability', async () => {
        await lib.clickOnElement(global.page, cellSelector(data.event1, data.roleB));
        await waitForPanelMembers([data.bert, data.cora]);

        await lib.clickOnElement(global.page, cellSelector(data.event1, data.roleA));
        await waitForPanelMembers([data.anna, data.bert]);
    });

    test('click on event column header toggles select all/nothing and keeps utilization of tokens', async () => {
        const selectedCellsCount = () => global.page.evaluate((grid) => document.querySelectorAll(`${grid} td.x-grid3-cell-selected .cs-members-cell`).length, GRID);
        // NOTE: tokens are rendered white (invalid gradient) if utilization can't be computed
        const invalidTokenStyles = () => global.page.evaluate((panel) => {
            return Array.from(document.querySelectorAll(`${panel} table.cs-member-token`))
                .filter((token) => /NaN/.test(token.getAttribute('style')) || getComputedStyle(token).backgroundImage === 'none')
                .map((token) => token.querySelector('.cs-name').textContent.trim());
        }, PANEL);

        await lib.clickOnElementWithLabel(global.page, `${GRID} .x-grid3-hd-inner`, 'Termin');
        await global.page.waitForFunction((grid) => {
            const cells = document.querySelectorAll(`${grid} .cs-members-cell`);
            const selected = document.querySelectorAll(`${grid} td.x-grid3-cell-selected .cs-members-cell`);
            return cells.length > 0 && cells.length === selected.length;
        }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, GRID);
        // event column itself is not selected
        expect(await global.page.$$(`${GRID} td.x-grid3-cell-selected.x-grid3-td-0`)).toHaveLength(0);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
        expect(await invalidTokenStyles()).toEqual([]);

        await lib.clickOnElementWithLabel(global.page, `${GRID} .x-grid3-hd-inner`, 'Termin');
        await global.page.waitForFunction((grid) => document.querySelectorAll(`${grid} td.x-grid3-cell-selected`).length === 0,
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, GRID);
        expect(await selectedCellsCount()).toBe(0);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
        expect(await invalidTokenStyles()).toEqual([]);
    });

    test('drag member into a cell', async () => {
        await waitForPanelCount(data.anna, 0);

        await dragAndDrop(() => panelTokenHandle(data.anna), () => global.page.$(cellSelector(data.event1, data.roleA)));
        await waitForDragFinished();

        const cellToken = await cellTokenHandle(data.event1, data.roleA);
        expect(await cellToken.evaluate((el) => el.querySelector('.cs-name').textContent.trim())).toBe(data.anna.name);
        await waitForPanelCount(data.anna, 1);
        expect(await isApplyButtonDisabled()).toBe(false);
    });

    test('member counts survive rerendering of member tokens', async () => {
        // selecting a cell rerenders the member tokens
        await lib.clickOnElement(global.page, cellSelector(data.event2, data.roleA));
        await waitForPanelMembers([data.anna, data.bert]);
        await waitForPanelCount(data.anna, 1);

        await lib.clickOnElement(global.page, cellSelector(data.event2, data.roleB));
        await waitForPanelMembers([data.bert, data.cora]);

        // select all / nothing
        await lib.clickOnElementWithLabel(global.page, `${GRID} .x-grid3-hd-inner`, 'Termin');
        await lib.clickOnElementWithLabel(global.page, `${GRID} .x-grid3-hd-inner`, 'Termin');
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
        await waitForPanelCount(data.anna, 1);
        await waitForPanelCount(data.bert, 0);

        const counts = await getPanelCounts();
        expect(Object.values(counts).filter((count) => !/^\d+$/.test(count))).toEqual([]);
    });

    test('save changes', async () => {
        await applyChanges();

        expect(await getRoleAttendeeIds(data.event1, data.roleA)).toEqual([data.anna.id]);
        await cellTokenHandle(data.event1, data.roleA);
        await waitForPanelCount(data.anna, 1);
    });

    test('cancel removing a member by closing the confirm dialog', async () => {
        await dragAndDrop(() => cellTokenHandle(data.event1, data.roleA), () => global.page.$(PANEL));

        await global.page.waitForSelector('.modal.vue-message-box.show', {visible: true});
        await lib.clickOnElement(global.page, '.modal.vue-message-box.show .btn-close');
        await lib.waitForElement(global.page, '.modal.vue-message-box', {hidden: true});
        await lib.waitForElement(global.page, '.modal-backdrop', {hidden: true});

        // token is not stuck in the air but back in the cell
        await waitForDragFinished();
        await lib.waitForElement(global.page, `${GRID} .cs-member-cell-disabled`, {hidden: true});
        await cellTokenHandle(data.event1, data.roleA);
        await waitForPanelCount(data.anna, 1);
        expect(await isApplyButtonDisabled()).toBe(true);
    });

    test('remove a member by dragging it back', async () => {
        await dragAndDrop(() => cellTokenHandle(data.event1, data.roleA), () => global.page.$(PANEL));
        await lib.answerMessageBox(global.page, 'yes');
        await waitForDragFinished();

        await lib.waitForElement(global.page, `${cellSelector(data.event1, data.roleA)} table.cs-member-token`, {hidden: true});
        await waitForPanelCount(data.anna, 0);

        await applyChanges();
        expect(await getRoleAttendeeIds(data.event1, data.roleA)).toEqual([]);
    });

    test('hidden role columns filter members, also after reloading data', async () => {
        await toggleRoleColumn(data.roleB);
        expect(await isRoleColumnVisible(data.roleB)).toBe(false);
        // Cora is capable for role B only
        await waitForPanelMembers([data.anna, data.bert]);

        await lib.clickOnElementWithLabel(global.page, BUTTON, 'Neu laden');
        await waitForCrewSchedulingLoaded();
        await waitForGridEvents([data.event1, data.event2]);
        await waitForPanelMembers([data.anna, data.bert]);

        await toggleRoleColumn(data.roleB);
        expect(await isRoleColumnVisible(data.roleB)).toBe(true);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);
    });

    test('filter events', async () => {
        let popup = await lib.getEditDialog('Filter', global.page, {selector: BUTTON});
        await lib.formFillInputField(popup, {selector: '.tw-ftb-frow-value input', value: 'Abendmesse'});
        await lib.clickOnElementWaitPageClosed(popup, '.x-btn-text', 'Ok');

        await waitForCrewSchedulingLoaded();
        await waitForGridEvents([data.event2]);
        await waitForPanelMembers([data.anna, data.bert, data.cora]);

        // filter button shows number of filters
        popup = await lib.getEditDialog('Filter 1', global.page, {selector: BUTTON});
        const valueField = '.tw-ftb-frow-value input';
        await popup.waitForFunction((sel, value) => document.querySelector(sel)?.value === value,
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, valueField, 'Abendmesse');
        await clearField(popup, '.tw-ftb-frow-value');
        await lib.clickOnElementWaitPageClosed(popup, '.x-btn-text', 'Ok');

        await waitForCrewSchedulingLoaded();
        await waitForGridEvents([data.event1, data.event2]);
    });
});
