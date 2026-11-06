import lib from '../../lib/browser.js';

// the avatar in the TineBar shows the color and the image of the own contact
// NOTE: dialogs open as browser popups (default of preference windowtype)
let originalContact = null;

// contact edit dialog (popup)
let dialog = null;

const openOwnContact = async () => {
    const user = await lib.getCurrentUser(global.page);
    dialog = await lib.getEditDialog(user.accountDisplayName, null,
        {selector: '.t-app-addressbook .x-grid3-col-n_fileas', clickCount: 2, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    await dialog.waitForSelector('.ux-imagefield-ct', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    await dialog.waitForFunction(() => !document.querySelector('.ext-el-mask')?.offsetParent, {timeout: lib.getEnvInt('TEST_TIMEOUT_MASK')});
};

const PROVENANCE_TITLE = 'Bitte die Datenherkunft bestätigen';

const saveContact = async () => {
    const closed = new Promise((resolve) => dialog.once('close', resolve));
    await lib.clickOnElementWithLabel(dialog, '.x-toolbar-right-row button', 'Ok');

    // GDPR: confirm data provenance if asked
    const provenance = dialog.waitForFunction((title) => Array.from(document.querySelectorAll('.x-window-header-text')).some((el) => el.textContent.trim() === title && el.offsetParent),
        {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, PROVENANCE_TITLE).then(() => true, () => false);
    if (await Promise.race([closed.then(() => false), provenance])) {
        for (const win of await dialog.$$('.x-window')) {
            const isProvenance = await win.evaluate((el, title) => el.querySelector('.x-window-header-text')?.textContent.trim() === title && !!el.offsetParent, PROVENANCE_TITLE);
            if (isProvenance) {
                const [okBtn] = await win.$$('xpath/.//button[normalize-space(.)="Ok"]');
                await okBtn.click();
            }
        }
    }

    await closed;
};

// NOTE: the avatar loads the contact once
const reloadPage = async () => {
    await global.page.reload({waitUntil: 'domcontentloaded'});
    await lib.waitForPageHeader(global.page);
};

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
    originalContact = await global.page.evaluate(async () => {
        const account = Tine.Tinebase.registry.get('currentAccount');
        return await Tine.Addressbook.getContact(account.contact_id);
    });
});

describe('Contact avatar', () => {
    test('set contact color in the contact dialog', async () => {
        await openOwnContact();

        await dialog.click('.ux-imagefield-ct', {button: 'right'});
        await lib.clickOnElementWithLabel(dialog, '.x-menu .x-menu-item-text', 'Farbe');
        await dialog.waitForSelector('.x-color-menu', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
        await lib.clickOnElement(dialog, '.x-color-menu a.color-339966');

        // image field shows a border in the contact color
        await dialog.waitForFunction(() => getComputedStyle(document.querySelector('.ux-imagefield-ct')).borderTopColor === 'rgb(51, 153, 102)',
            {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});

        await saveContact();
    });

    test('shows initials with the contact color', async () => {
        await reloadPage();

        await global.page.waitForSelector('.account-user-avatar__image_alt', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        await global.page.waitForFunction(() => getComputedStyle(document.querySelector('.account-user-avatar__image_alt')).backgroundColor === 'rgb(51, 153, 102)',
            {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        const color = await global.page.evaluate(() => getComputedStyle(document.querySelector('.account-user-avatar__image_alt')).color);
        expect(color).toBe('rgb(255, 255, 255)');
    });

    test('upload contact image in the contact dialog', async () => {
        // page was reloaded in the addressbook
        await openOwnContact();

        const input = await dialog.$('input[type=file]');
        await input.uploadFile('src/test/Addressbook/contactImage.png');
        await dialog.waitForFunction(() => /location=tempFile/.test(document.querySelector('.ux-imagefield-ct img')?.src || ''),
            {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});

        await saveContact();
    });

    test('shows the contact image with a border in the contact color', async () => {
        await reloadPage();

        await global.page.waitForSelector('.account-user-avatar__image', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        const image = await global.page.evaluate(() => {
            const style = getComputedStyle(document.querySelector('.account-user-avatar__image'));
            return {border: `${style.borderTopWidth} ${style.borderTopStyle} ${style.borderTopColor}`, boxSizing: style.boxSizing};
        });
        expect(image).toStrictEqual({border: '2px solid rgb(51, 153, 102)', boxSizing: 'border-box'});
        lib.assertNotAborted('Contact avatar');
    });
});

afterAll(async () => {
    // restore contact color and image
    if (originalContact) {
        await global.page.evaluate(async (contact) => {
            const current = await Tine.Addressbook.getContact(contact.id);
            const hadImage = String(contact.jpegphoto).startsWith('index.php');
            await Tine.Addressbook.saveContact({...current, color: contact.color, jpegphoto: hadImage ? contact.jpegphoto : ''});
        }, originalContact);
    }
    await lib.closeBrowser();
});
