import lib from '../../lib/browser.js';

// supplier of a purchase invoice: the invoice keeps a copy (denormalization) of the supplier
// the edit icon of the supplier field offers to edit the copy or the original supplier, the copy dialog links to the original as well
// after editing the original its changes are taken into the copy
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
const MENU_ITEM = '.x-menu-item-text';
const EDIT_COPY = 'Diese Kopie bearbeiten';
const COPY_INFO = `.x-menu-item:has(${MENU_ITEM}) .field-description`;
const COPY_DESCRIPTION = 'Die zugrundeliegende Eingangsrechnung pflegt eine eigene Kopie. Änderungen an dieser Kopie gelten nur für diese Eingangsrechnung, der original Lieferant bleibt unverändert.';
const EDIT_ORIGINAL = 'Original bearbeiten';

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

// the edit icon of the supplier field shows a menu to edit the copy or the original
const openEditMenu = async (invoiceDialog) => {
    await lib.clickOnElement(invoiceDialog, SUPPLIER_EDIT);
    await invoiceDialog.waitForSelector(MENU_ITEM, {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
    expect(await invoiceDialog.$$eval(MENU_ITEM, (items) => items.filter((item) => item.offsetParent)
        .map((item) => item.textContent.trim()))).toStrictEqual([EDIT_COPY, EDIT_ORIGINAL]);
    // the quicktip of the edit icon must not cover the menu
    expect(await invoiceDialog.$$eval(MENU_ITEM, (items) => items.filter((item) => item.offsetParent).every((item) => {
        const rect = item.getBoundingClientRect();
        return item.contains(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2));
    }))).toBe(true);
};

// edit the copy of the supplier with the edit menu of the supplier field
const openSupplierCopy = async (invoiceDialog) => {
    await openEditMenu(invoiceDialog);
    const copyDialog = await openPopupBy(invoiceDialog, () => lib.clickOnElementWithLabel(invoiceDialog, MENU_ITEM, EDIT_COPY));
    await copyDialog.waitForSelector('.alert a', {visible: true, timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    return copyDialog;
};

// edit the original supplier with the link in the notice of the copy dialog
const editOriginalFromCopy = async (copyDialog) => {
    await editOriginal(await openPopupBy(copyDialog, () => lib.clickOnElementWithLabel(copyDialog, '.alert a', 'original Lieferant')));
};

// edit the original supplier with the edit menu of the supplier field
const editOriginalFromMenu = async (invoiceDialog) => {
    await openEditMenu(invoiceDialog);
    await editOriginal(await openPopupBy(invoiceDialog, () => lib.clickOnElementWithLabel(invoiceDialog, MENU_ITEM, EDIT_ORIGINAL)));
};

const editOriginal = async (originalDialog) => {
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

// changes of the original are taken into the copy of the supplier field
// NOTE: the copy is not shown in the field, it's checked in the copy dialog afterwards
const waitForFieldCopy = (invoiceDialog, values) => invoiceDialog.waitForFunction((expected) => {
    const copy = Ext.ComponentMgr.all.find((cmp) => cmp.name === 'supplier_id')?.selectedRecord;
    return !!copy && Object.entries(expected).every(([name, value]) => copy.get(name) === value);
}, {timeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')}, values);

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

// creates the supplier and an invoice with this supplier, an existing invoice is saved and reopened
const openInvoiceWithSupplier = async (supplierName, externalNumber, existing) => {
    const supplier = await createSupplier(supplierName);
    let invoiceDialog = await openNewInvoice();
    await selectSupplier(invoiceDialog, supplier);
    await lib.formFillInputField(invoiceDialog, {selector: 'input[name="external_invoice_number"]', value: externalNumber});

    if (existing) {
        await lib.clickOnElementWaitPageClosed(invoiceDialog, OK_BUTTON, 'Ok', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        invoiceDialog = await openExistingInvoice(externalNumber);
    }
    return {supplier, invoiceDialog};
};

const closePopups = async (popups) => {
    // don't let a failed case block the next one
    for (const popup of popups) {
        if (popup && !popup.isClosed()) {
            await popup.close();
        }
    }
};

const invoices = [
    {invoice: 'new invoice', existing: false},
    {invoice: 'existing invoice', existing: true},
];

const cases = [
    {name: 'without local changes', answer: null, expected: CHANGED},
    {name: 'with local changes overwritten by the original', answer: 'yes', expected: CHANGED},
    {name: 'with local changes kept', answer: 'no', expected: {...CHANGED, ...LOCAL}},
];

describe('Edit menu of the supplier field', () => {
    const externalNumber = `E2E-${suffix}-info`;
    let invoiceDialog = null;

    test('open invoice and select supplier', async () => {
        ({invoiceDialog} = await openInvoiceWithSupplier(`E2E Lieferant ${suffix} Info`, externalNumber, false));
        await openEditMenu(invoiceDialog);
    });

    test('info icon of the copy explains the copy', async () => {
        await invoiceDialog.hover(COPY_INFO);
        await invoiceDialog.waitForFunction((text) => Array.from(document.querySelectorAll('.x-tip'))
            .some((tip) => tip.offsetParent && tip.textContent.replace(/\s+/g, ' ').trim() === text), {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')}, COPY_DESCRIPTION);
    });

    test('click on info icon does not edit the copy', async () => {
        const pages = (await global.browser.pages()).length;
        await lib.clickOnElement(invoiceDialog, COPY_INFO);

        expect(await invoiceDialog.$eval(COPY_INFO, (el) => !!el.offsetParent)).toBe(true);
        expect((await global.browser.pages()).length).toBe(pages);
    });

    afterAll(async () => {
        await closePopups([invoiceDialog]);
    });
});

describe.each(invoices)('Edit original supplier from the supplier copy of a $invoice', ({existing}) => {
    describe.each(cases)('$name', ({name, answer, expected}) => {
        const externalNumber = `E2E-${suffix}-copy-${existing ? 'existing' : 'new'}-${answer || 'none'}`;
        let supplier = null;
        let invoiceDialog = null;
        let copyDialog = null;

        test('open invoice and select supplier', async () => {
            ({supplier, invoiceDialog} = await openInvoiceWithSupplier(
                `E2E Lieferant ${suffix} Kopie ${existing ? 'bestehend' : 'neu'} ${answer || 'ohne'}`, externalNumber, existing));
        });

        test('edit supplier copy', async () => {
            copyDialog = await openSupplierCopy(invoiceDialog);
            expect(await getFieldValues(copyDialog)).toStrictEqual(ORIGINAL);

            if (answer) {
                await lib.formFillInputField(copyDialog, {selector: 'textarea[name="description"]', value: LOCAL.description});
            }
        });

        test('edit original supplier', async () => {
            await editOriginalFromCopy(copyDialog);
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
            await closePopups([copyDialog, invoiceDialog]);
        });
    });
});

describe.each(invoices)('Edit original supplier from the edit menu of the supplier field of a $invoice', ({existing}) => {
    describe.each(cases)('$name', ({name, answer, expected}) => {
        const externalNumber = `E2E-${suffix}-menu-${existing ? 'existing' : 'new'}-${answer || 'none'}`;
        let supplier = null;
        let invoiceDialog = null;
        let copyDialog = null;

        test('open invoice and select supplier', async () => {
            ({supplier, invoiceDialog} = await openInvoiceWithSupplier(
                `E2E Lieferant ${suffix} Menü ${existing ? 'bestehend' : 'neu'} ${answer || 'ohne'}`, externalNumber, existing));
        });

        if (answer) {
            test('edit supplier copy', async () => {
                copyDialog = await openSupplierCopy(invoiceDialog);
                expect(await getFieldValues(copyDialog)).toStrictEqual(ORIGINAL);

                await lib.formFillInputField(copyDialog, {selector: 'textarea[name="description"]', value: LOCAL.description});
                await lib.clickOnElementWaitPageClosed(copyDialog, OK_BUTTON, 'Ok', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
            });
        }

        test('edit original supplier', async () => {
            await editOriginalFromMenu(invoiceDialog);
            expect(await getOriginal(supplier)).toStrictEqual(CHANGED);
        });

        test(answer ? `asks to overwrite local changes (${answer})` : 'takes changes of the original', async () => {
            if (answer) {
                await lib.answerMessageBox(invoiceDialog, answer, {title: QUESTION_TITLE});
            }
            await waitForFieldCopy(invoiceDialog, expected);
            // no question without local changes
            expect(await invoiceDialog.$(QUESTION)).toBeNull();
        });

        test('supplier copy shows the changes', async () => {
            copyDialog = await openSupplierCopy(invoiceDialog);
            expect(await getFieldValues(copyDialog)).toStrictEqual(expected);
            await lib.clickOnElementWaitPageClosed(copyDialog, OK_BUTTON, 'Abbrechen', {closeTimeout: lib.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
        });

        test('save invoice', async () => {
            await saveInvoice(invoiceDialog);

            expect(await getSavedSupplierCopy(externalNumber)).toStrictEqual([{original_id: supplier.id, ...expected}]);
            // the original is not changed by the copy
            expect(await getOriginal(supplier)).toStrictEqual(CHANGED);
            lib.assertNotAborted(name);
        });

        afterAll(async () => {
            await closePopups([copyDialog, invoiceDialog]);
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
