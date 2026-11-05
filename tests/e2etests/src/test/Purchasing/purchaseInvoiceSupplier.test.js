import lib from '../../lib/browser.js';

// supplier of a purchase invoice: the invoice keeps a copy (denormalization) of the supplier
// the copy dialog links to the original supplier, after editing the original its changes are taken into the copy
// NOTE: for a new invoice the server has no copy yet, for an existing invoice the server updates the copy itself
// NOTE: dialogs open as browser popups (default of preference windowtype)
const suffix = Math.round(Math.random() * 10000000);
const ORIGINAL = {description: 'Beschreibung original', vatid: `DE${suffix}`};
const CHANGED = {description: 'Beschreibung im Original geändert', vatid: `DE${suffix}9`};
const LOCAL = {description: 'Beschreibung lokal geändert'};
const QUESTION_TITLE = 'Änderungen verwerfen?';

const SUPPLIER_FIELD = 'input[name="supplier_id"]';
const SUPPLIER_EDIT = `.x-form-field-wrap:has(${SUPPLIER_FIELD}) .action_edit`;
const OK_BUTTON = '.x-toolbar-right-row button';

const suppliers = [];

const getFieldValues = (page) => page.evaluate(() => ({
    description: document.querySelector('textarea[name="description"]').value,
    vatid: document.querySelector('input[name="vatid"]').value,
}));

// opens a popup by clicking an element (not a button with a label)
const openPopupBy = async (page, click) => {
    const popupPromise = lib.getNewWindow(lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY'));
    popupPromise.catch(() => {});
    await click();
    const popup = await popupPromise;
    await lib.waitForAppReady(popup);
    return popup;
};

const createSupplier = async (name) => {
    const supplier = await global.page.evaluate(async (data) => await Tine.Sales.saveSupplier(data), {name, currency: 'EUR', ...ORIGINAL});
    suppliers.push(supplier);
    return supplier;
};

const selectSupplier = async (invoiceDialog, supplier) => {
    await lib.clickOnElement(invoiceDialog, SUPPLIER_FIELD);
    await invoiceDialog.keyboard.type(supplier.name, {delay: 20});
    await lib.clickOnElementWithLabel(invoiceDialog, '.x-combo-list-item', `${supplier.number} - ${supplier.name}`);
    await invoiceDialog.waitForSelector(SUPPLIER_EDIT, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
};

const openNewInvoice = async () => {
    const invoiceDialog = await lib.getEditDialog('Eingangsrechnung hinzufügen');
    await invoiceDialog.waitForSelector(SUPPLIER_FIELD, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    return invoiceDialog;
};

const openExistingInvoice = async (externalNumber) => {
    const invoiceDialog = await lib.getEditDialog(externalNumber, null,
        {selector: '.x-grid3-col-external_invoice_number', clickCount: 2, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    await invoiceDialog.waitForSelector(SUPPLIER_EDIT, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    return invoiceDialog;
};

// edit the copy of the supplier with the edit icon of the supplier field
const openSupplierCopy = async (invoiceDialog) => {
    const copyDialog = await openPopupBy(invoiceDialog, () => invoiceDialog.click(SUPPLIER_EDIT));
    await copyDialog.waitForSelector('.alert a', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    return copyDialog;
};

// edit the original supplier with the link in the notice of the copy dialog
const editOriginal = async (copyDialog) => {
    const originalDialog = await openPopupBy(copyDialog, () => lib.clickOnElementWithLabel(copyDialog, '.alert a', 'original Lieferant'));
    await originalDialog.waitForSelector('textarea[name="description"]', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});

    // the original dialog is no copy
    expect(await originalDialog.$('.alert a')).toBeNull();
    expect(await getFieldValues(originalDialog)).toStrictEqual(ORIGINAL);

    await lib.formFillInputField(originalDialog, {selector: 'textarea[name="description"]', value: CHANGED.description});
    await lib.formFillInputField(originalDialog, {selector: 'input[name="vatid"]', value: CHANGED.vatid});
    await lib.clickOnElementWaitPageClosed(originalDialog, OK_BUTTON, 'Ok', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
};

const QUESTION = '.vue-message-box.show';

// saves the invoice, fails with the shown message (regression: concurrency conflict)
const saveInvoice = async (invoiceDialog) => {
    const closed = new Promise((resolve) => invoiceDialog.once('close', () => resolve('closed')));
    await lib.clickOnElementWithLabel(invoiceDialog, OK_BUTTON, 'Ok');
    const message = invoiceDialog.waitForSelector(`${QUESTION} .modal-content`, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')})
        // NOTE: waiting fails as well if the dialog gets closed
        .then((el) => el.evaluate((content) => content.textContent.replace(/\s+/g, ' ').trim()), (err) => (err.name === 'TimeoutError' ? 'not closed' : closed));
    expect(await Promise.race([closed, message])).toBe('closed');
};

// changes of the original are taken into the copy dialog
const waitForCopyValues = (copyDialog, values) => copyDialog.waitForFunction((expected) => Object.entries(expected)
    .every(([name, value]) => document.querySelector(`[name="${name}"]`).value === value), {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, values);

const getSavedSupplierCopy = (externalNumber) => global.page.evaluate(async (number) => {
    const {results} = await Tine.Sales.searchDocument_PurchaseInvoices([{field: 'external_invoice_number', operator: 'equals', value: number}], {});
    return results.map((invoice) => ({
        original_id: invoice.supplier_id.original_id,
        description: invoice.supplier_id.description,
        vatid: invoice.supplier_id.vatid,
    }));
}, externalNumber);

const getOriginal = (supplier) => global.page.evaluate(async (id) => {
    const original = await Tine.Sales.getSupplier(id);
    return {description: original.description, vatid: original.vatid};
}, supplier.id);

beforeAll(async () => {
    await lib.getBrowser('Einkauf');
    await lib.clickOnElementWithLabel(global.page, '.tine-mainscreen-centerpanel-west .x-tree-node a span', 'Eingangsrechnungen');
    await lib.waitForAppReady(global.page);
});

const cases = [
    {name: 'without local changes', answer: null, expected: CHANGED},
    {name: 'with local changes overwritten by the original', answer: 'yes', expected: CHANGED},
    {name: 'with local changes kept', answer: 'no', expected: {...CHANGED, ...LOCAL}},
];

describe.each([
    {invoice: 'new invoice', existing: false},
    {invoice: 'existing invoice', existing: true},
])('Edit original supplier from the supplier copy of a $invoice', ({existing}) => {
    describe.each(cases)('$name', ({name, answer, expected}) => {
        const externalNumber = `E2E-${suffix}-${existing ? 'existing' : 'new'}-${answer || 'none'}`;
        let supplier = null;
        let invoiceDialog = null;
        let copyDialog = null;

        test('open invoice and select supplier', async () => {
            supplier = await createSupplier(`E2E Lieferant ${suffix} ${existing ? 'bestehend' : 'neu'} ${answer || 'ohne'}`);
            invoiceDialog = await openNewInvoice();
            await selectSupplier(invoiceDialog, supplier);
            await lib.formFillInputField(invoiceDialog, {selector: 'input[name="external_invoice_number"]', value: externalNumber});

            if (existing) {
                await lib.clickOnElementWaitPageClosed(invoiceDialog, OK_BUTTON, 'Ok', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
                invoiceDialog = await openExistingInvoice(externalNumber);
            }
        });

        test('edit supplier copy', async () => {
            copyDialog = await openSupplierCopy(invoiceDialog);
            expect(await getFieldValues(copyDialog)).toStrictEqual(ORIGINAL);

            if (answer) {
                await lib.formFillInputField(copyDialog, {selector: 'textarea[name="description"]', value: LOCAL.description});
            }
        });

        test('edit original supplier', async () => {
            await editOriginal(copyDialog);
            expect(await getOriginal(supplier)).toStrictEqual(CHANGED);
        });

        test(answer ? `asks to overwrite local changes (${answer})` : 'takes changes of the original', async () => {
            if (answer) {
                await lib.answerMessageBox(copyDialog, answer, {title: QUESTION_TITLE});
            }
            await waitForCopyValues(copyDialog, expected);
            expect(await getFieldValues(copyDialog)).toStrictEqual(expected);
            // no question without local changes
            expect(await copyDialog.$(QUESTION)).toBeNull();
        });

        test('save supplier copy', async () => {
            await lib.clickOnElementWaitPageClosed(copyDialog, OK_BUTTON, 'Ok', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        });

        test('save invoice', async () => {
            await saveInvoice(invoiceDialog);

            expect(await getSavedSupplierCopy(externalNumber)).toStrictEqual([{original_id: supplier.id, ...expected}]);
            // the original is not changed by the copy
            expect(await getOriginal(supplier)).toStrictEqual(CHANGED);
            lib.assertNotAborted(name);
        });

        afterAll(async () => {
            // don't let a failed case block the next one
            for (const popup of [copyDialog, invoiceDialog]) {
                if (popup && !popup.isClosed()) {
                    await popup.close();
                }
            }
        });
    });
});

afterAll(async () => {
    await global.page.evaluate(async (supplierIds, number) => {
        const {results} = await Tine.Sales.searchDocument_PurchaseInvoices([{field: 'external_invoice_number', operator: 'startswith', value: number}], {});
        if (results.length) {
            await Tine.Sales.deleteDocument_PurchaseInvoices(results.map((invoice) => invoice.id));
        }
        await Tine.Sales.deleteSuppliers(supplierIds);
    }, suppliers.map((supplier) => supplier.id), `E2E-${suffix}-`);
    await lib.closeBrowser();
});
