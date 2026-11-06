import lib from '../../lib/browser.js';

// event types (VMultiPicker) in the event edit dialog
// NOTE: dialogs open as browser popups (default of preference windowtype)
const suffix = Math.round(Math.random() * 10000000);
const eventTypes = [
    {name: `Kurz ${suffix}`, color: '#339966'},
    {name: `Meeting ohne Farbe ${suffix}`},
    {name: `Schulung mit externem Referenten ${suffix}`, color: '#3366FF'},
    {name: `Kundentermin vor Ort beim Kunden ${suffix}`},
];

const TAGS = '.record-tag-container > .record:not(#ellipsis-show-more)';
const ELLIPSIS = '#ellipsis-show-more';

// event edit dialog (popup)
let dialog = null;
let dialogHeight = 0;

const getPickerState = () => dialog.evaluate(() => {
    const root = document;
    const tags = Array.from(root.querySelectorAll('.record-tag-container > .record:not(#ellipsis-show-more)'));
    const titleEls = tags.map((tag) => tag.querySelector('.record-title'));
    return {
        titles: titleEls.map((el) => el.textContent.trim()),
        truncated: titleEls.map((el) => el.scrollWidth > el.clientWidth),
        widths: tags.map((tag) => tag.getBoundingClientRect().width),
        ellipsis: !!root.querySelector('#ellipsis-show-more'),
        available: root.querySelector('.record-tag-container').parentElement.clientWidth,
    };
});

const getDialogWidth = () => dialog.evaluate(() => window.innerWidth);

// resizes the popup window
const resizeDialogTo = async (width) => {
    width = Math.round(width);
    await dialog.setViewport({width, height: dialogHeight});
    // NOTE: the dialog layout follows the window size buffered, the picker measures its tags afterwards
    await dialog.waitForFunction((w) => {
        const available = document.querySelector('.record-tag-container')?.parentElement.clientWidth;
        if (document.querySelector('.tw-editdialog').getBoundingClientRect().width !== w || window.__e2eAvailable !== available) {
            window.__e2eAvailable = available;
            window.__e2eStableFrames = 0;
            return false;
        }
        return ++window.__e2eStableFrames >= 10;
    }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE'), polling: 'raf'}, width);
};

const waitForPicker = (predicate) => dialog.waitForFunction(predicate, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE'), polling: 'raf'});

const allTagsInFull = () => {
    const root = document;
    const titles = Array.from(root.querySelectorAll('.record-tag-container .record-title'));
    return titles.length === 4 && !root.querySelector('#ellipsis-show-more') && titles.every((el) => el.textContent.trim() && el.scrollWidth <= el.clientWidth);
};

// natural width of all tags
let naturalWidth = 0;
// change of the picker width per dialog width
let pickerWidthRatio = 1;

// returns the shown popover (teleported to body)
const getShownPopover = () => dialog.evaluate(() => {
    const popover = Array.from(document.querySelectorAll('.popover.show')).pop();
    if (!popover) return null;
    const rect = popover.getBoundingClientRect();
    return {
        text: popover.textContent.replace(/\s+/g, ' ').trim(),
        height: rect.height,
        // popover is not covered by other elements
        onTop: !!document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2)?.closest('.popover.show'),
        tagTitles: Array.from(popover.querySelectorAll('.record-title')).map((el) => el.textContent.trim()),
    };
});

const waitForPopover = () => dialog.waitForSelector('.popover.show', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});

const closePopover = async () => {
    // click outside of the popover (bottom left corner of the dialog, the popover might cover the fields above the picker)
    await dialog.mouse.click(5, dialogHeight - 5);
    await dialog.waitForFunction(() => !document.querySelector('.popover.show'), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
};

const addEventType = async (name, isFirst) => {
    // the search field is replaced by the tags once the first event type is selected -> click into the empty area right of the tags
    if (isFirst) {
        await lib.clickOnElement(dialog, 'input[name="event_types"]');
    } else {
        const box = await (await dialog.$('.main-container')).boundingBox();
        await dialog.mouse.click(box.x + box.width - 40, box.y + box.height / 2);
    }
    await dialog.keyboard.type(String(suffix), {delay: 50});
    await lib.clickOnElementWithLabel(dialog, '.x-combo-list-item', name);
};

beforeAll(async () => {
    await lib.getBrowser('Kalender');
    // test data
    await global.page.evaluate(async (types) => {
        window.__e2eEventTypes = [];
        for (const type of types) {
            window.__e2eEventTypes.push(await Tine.Calendar.saveEventType(type));
        }
    }, eventTypes);
});

describe('Event types in the event dialog', () => {
    test('open event dialog', async () => {
        dialog = await lib.getEditDialog('Termin hinzufügen', null, {selector: '.t-app-calendar .x-btn-text'});
        await dialog.waitForSelector('input[name="event_types"]', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        dialogHeight = await dialog.evaluate(() => window.innerHeight);
        // enough space to add all event types
        await resizeDialogTo(1300);
    });

    test('add event types', async () => {
        for (const [idx, type] of eventTypes.entries()) {
            await addEventType(type.name, idx === 0);
            // all tags with their titles are shown
            await dialog.waitForFunction((count) => {
                const titles = Array.from(document.querySelectorAll('.record-tag-container .record-title'));
                return titles.length === count && titles.every((el) => el.textContent.trim());
            }, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE'), polling: 'raf'}, idx + 1);
        }
    });

    test('shows all tags in full if there is enough space', async () => {
        await waitForPicker(allTagsInFull);
        const state = await getPickerState();

        expect([...state.titles].sort()).toStrictEqual(eventTypes.map((t) => t.name).sort());
        naturalWidth = state.widths.reduce((sum, w) => sum + w, 0);

        await resizeDialogTo(1200);
        pickerWidthRatio = (state.available - (await getPickerState()).available) / 100;
    });

    test('shrinks the widest tags if space gets short', async () => {
        // 80% of the needed space
        const {available} = await getPickerState();
        await resizeDialogTo(1200 - (available - naturalWidth * 0.8) / pickerWidthRatio);
        await waitForPicker(() => {
            const root = document;
            return !root.querySelector('#ellipsis-show-more') && Array.from(root.querySelectorAll('.record-tag-container .record-title')).some((el) => el.scrollWidth > el.clientWidth);
        });
        const state = await getPickerState();

        expect(state.titles).toHaveLength(4);
        // the short tag keeps its natural width
        expect(state.truncated[state.titles.indexOf(eventTypes[0].name)]).toBe(false);
        // all truncated tags share the same width
        const truncatedWidths = state.widths.filter((w, idx) => state.truncated[idx]).map(Math.round);
        expect(Math.max(...truncatedWidths) - Math.min(...truncatedWidths)).toBeLessThanOrEqual(1);
    });

    test('shows the full title of a truncated tag in a popover', async () => {
        const state = await getPickerState();
        const tags = await dialog.$$(TAGS);
        await tags[state.truncated.indexOf(true)].click();

        await waitForPopover();
        const popover = await getShownPopover();

        expect(popover.text).toBe(state.titles[state.truncated.indexOf(true)]);
        expect(popover.height).toBeGreaterThan(0);
        expect(popover.onTop).toBe(true);

        await closePopover();
    });

    test('moves tags into the overflow popover only if space is tight', async () => {
        // NOTE: the dialog switches to a responsive layout if it gets narrow, so shrink it step by step
        while (!(await getPickerState()).ellipsis) {
            if (await getDialogWidth() < 350) {
                throw new Error('no overflow although the dialog is narrow');
            }
            await resizeDialogTo(await getDialogWidth() - 50);
        }
        const state = await getPickerState();

        expect(state.titles.length).toBeGreaterThan(0);
        expect(state.titles.length).toBeLessThan(4);

        await lib.clickOnElement(dialog, ELLIPSIS);
        await waitForPopover();
        const popover = await getShownPopover();

        // regression: popover was 0px high
        expect(popover.height).toBeGreaterThan(0);
        expect(popover.onTop).toBe(true);
        expect([...state.titles, ...popover.tagTitles].sort()).toStrictEqual(eventTypes.map((t) => t.name).sort());

        await closePopover();
    });

    test('colors tags by the color of the event type', async () => {
        await resizeDialogTo(1300);
        await waitForPicker(allTagsInFull);

        const tags = await dialog.evaluate((sel) => Array.from(document.querySelectorAll(sel)).map((tag) => ({
            title: tag.querySelector('.record-title').textContent.trim(),
            background: getComputedStyle(tag).backgroundColor,
            color: getComputedStyle(tag).color,
        })), TAGS);
        const byTitle = Object.fromEntries(tags.map((tag) => [tag.title, tag]));

        expect(byTitle[eventTypes[0].name]).toMatchObject({background: 'rgb(51, 153, 102)', color: 'rgb(255, 255, 255)'});
        expect(byTitle[eventTypes[2].name]).toMatchObject({background: 'rgb(51, 102, 255)', color: 'rgb(255, 255, 255)'});
        // tags without color keep the default color
        expect(byTitle[eventTypes[1].name].background).toBe(byTitle[eventTypes[3].name].background);
        expect(byTitle[eventTypes[1].name].background).not.toBe(byTitle[eventTypes[0].name].background);
    });

    test('removes a tag', async () => {
        const before = await getPickerState();
        const removeBtns = await dialog.$$(`${TAGS} .remove-record`);
        await removeBtns[0].click();

        await dialog.waitForFunction((sel, count) => document.querySelectorAll(sel).length === count,
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, TAGS, before.titles.length - 1);
        const after = await getPickerState();

        expect(after.titles).toStrictEqual(before.titles.slice(1));
        lib.assertNotAborted('Event types in the event dialog');
    });
});

afterAll(async () => {
    // close dialog without saving
    if (dialog && !dialog.isClosed()) {
        await dialog.close();
    }
    await global.page.evaluate(async () => {
        await Tine.Calendar.deleteEventTypes((window.__e2eEventTypes || []).map((type) => type.id));
    });
    await lib.closeBrowser();
});
