const { expect: expectPuppeteer } = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Adressbuch', 'Kontakte');
});


describe('Contacts', () => {
    describe('Test MainPage', () => {
        test('choose grid fields', async () => {
            //await expectPuppeteer(page).toMatchElement('span', {text: process.env.TEST_USER});
            await expectPuppeteer(page).toMatchElement('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.click('.t-app-addressbook .ext-ux-grid-gridviewmenuplugin-menuBtn');
            await page.waitForSelector('.x-menu-list');
            await lib.makeScreenshot(page,{path: 'screenshots/Adressbuch/9_adressbuch_mit_spaltenauswahl.png'});
        });

        test('Import', async () => {
            let importDialog ;
            await expectPuppeteer(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await new Promise(r => setTimeout(r, 100)); // wait for btn to get active
            importDialog = lib.getNewWindow();
            await expectPuppeteer(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await importDialog;

            await expectPuppeteer(importDialog).toMatchElement('.x-btn-text', {text: 'Wählen Sie die Datei mit Ihren Kontakte'});
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test.csv');
            await expectPuppeteer(importDialog).toMatchElement('button', {text: new RegExp('test.csv.*'), timeout:10000})
            await lib.makeScreenshot(importDialog,{path: 'screenshots/Adressbuch/1_adressbuch_importfenster.png'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await lib.makeScreenshot(importDialog,{path: 'screenshots/Adressbuch/4_adressbuch_mit_import_optionen_setzen.png.png'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await expectPuppeteer(importDialog).toMatchElement('span', {text: 'Zusammenfassung'});
            await lib.makeScreenshot(importDialog,{path: 'screenshots/Adressbuch/8_adressbuch_mit_import_zusammenfassung.png'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Ende'});
            await importDialog.close();
        });

        test('Import conflicts', async () => {
            let importDialog ;
            await new Promise(r => setTimeout(r, 500));
            await expectPuppeteer(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await new Promise(r => setTimeout(r, 100)); // wait for btn to get active
            importDialog = lib.getNewWindow();
            await expectPuppeteer(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await importDialog;
            await expectPuppeteer(importDialog).toMatchElement('.x-btn-text', {text: 'Wählen Sie die Datei mit Ihren Kontakte'});
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test.csv');
            await expectPuppeteer(importDialog).toMatchElement('button', {text: new RegExp('test.csv.*')})
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await expectPuppeteer(importDialog).toMatchElement('span', {text: 'Konflikte auflösen'});
            await lib.makeScreenshot(importDialog,{path: 'screenshots/Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen.png'});
            await importDialog.close();
        });

        test.skip('Import conflicts', async () => {
            let importDialog ;
            await new Promise(r => setTimeout(r, 500));
            await expectPuppeteer(page).toMatchElement('.x-btn-text', {text: 'Kontakte importieren'});
            await new Promise(r => setTimeout(r, 100)); // wait for btn to get active
            importDialog = lib.getNewWindow();
            await expectPuppeteer(page).toClick('.x-btn-text', {text: 'Kontakte importieren'});
            importDialog = await importDialog;
            await expectPuppeteer(importDialog).toMatchElement('.x-btn-text', {text: 'Wählen Sie die Datei mit Ihren Kontakte'});
            await lib.uploadFile(importDialog, 'src/testScreenshots/Addressbook/test_fail.csv');
            await expectPuppeteer(importDialog).toMatchElement('button', {text: new RegExp('test_fail.csv.*')})
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Vorwärts'});
            await expectPuppeteer(importDialog).toMatchElement('span', {text: 'Konflikte auflösen'});
            await lib.makeScreenshot(importDialog,{path: 'screenshots/Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen.png'});
            await expectPuppeteer(importDialog).toClick('button', {text: 'Ende'});
            await importDialog.close();
        });

        describe('editDialog', () => {
            let popupWindow;

            test('open editDialog', async () => {
                await expectPuppeteer(page).toClick('.x-grid3-row-first');
                popupWindow = await lib.getEditDialog('Kontakt bearbeiten');
                await popupWindow.waitForSelector('.x-tab-strip.x-tab-strip-top',{timeout: 5000});
            });

            test('show map', async () => {
                try {
                    await expectPuppeteer(popupWindow).toClick('span', {text: 'Karte'});
                    await new Promise(r => setTimeout(r, 10000)); // wait to load map
                    await lib.makeScreenshot(popupWindow,{path: 'screenshots/1_adressverwaltung/12_adressbuch_kontakt_karte.png'});
                } catch (e) {
                    await console.log('Map musst enabled');
                }
            });

        test('notes', async () => {
            await selectTab(popupWindow, 'Notizen.*');
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/StandardBedienhinweise/17_standardbedienhinweise_hr_eingabemaske_neu_notiz.png'});
            await expectPuppeteer(popupWindow).toClick('button', {text: 'Notizen hinzufügen'});
            await popupWindow.waitForSelector('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            await popupWindow.click('.x-window-bwrap .x-form-trigger.x-form-arrow-trigger');
            //await new Promise(r => setTimeout(r, 1000));
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/StandardBedienhinweise/18_standardbedienhinweise_hr_eingabemaske_neu_notiz_notiz.png'});
            await expectPuppeteer(popupWindow).toClick('.x-window-bwrap button', 'Abbrechen');
        });

            test('attachments', async () => {
                await expectPuppeteer(popupWindow).toClick('span', {text: new RegExp("Anhänge.*")});
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/2_allgemeines/22_allgemein_hr_mitarbeiter_anhang.png'});
            });

            test('relations', async () => {
                await expectPuppeteer(popupWindow).toClick('span', {text: new RegExp("Verknüpfungen.*")});
                await new Promise(r => setTimeout(r, 3000));
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/2_allgemeines/23_allgemein_hr_mitarbeiter_verknuepfungen.png'});

                // Find relation panel by its unique column header
                const relationPanel = await popupWindow.evaluateHandle(() => {
                    const uniqueHeader = document.querySelector('.x-grid3-hd-related_model');
                    return uniqueHeader?.closest('.x-panel.x-wdgt-pickergrid.x-grid-panel');
                });

                let arrowtrigger = await relationPanel.$$('.x-form-arrow-trigger');
                await arrowtrigger[0].click(); // test!
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links.png'});
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/2_allgemeines/24_allgemein_hr_mitarbeiter_verknuepfungen_hinzu.png'});
                await arrowtrigger[1].click(); // test!
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts.png'});
            });

            test('history', async () => {
                await expectPuppeteer(popupWindow).toClick('span', {text: new RegExp("Historie.*")});
                await lib.makeScreenshot(popupWindow,{path: 'screenshots/2_allgemeines/21_allgemein_hr_mitarbeiter_historie.png'});
                await popupWindow.close();
            });
        });
    });
    describe('ContextMenu', () => {
        let felamimailIcon;

        test('test Tags', async () => {
            await new Promise(r => setTimeout(r, 1000));
            await expectPuppeteer(page).toClick('.x-grid3-row.x-grid3-row-first', {button: 'right'});
            await expectPuppeteer(page).toClick('.action_tag.x-menu-item-icon');
            await expectPuppeteer(page).toClick('.x-window .x-form-arrow-trigger');
            await page.waitForSelector('.x-widget-tag-tagitem-text');
            await page.hover('.x-widget-tag-tagitem-text');
            await lib.makeScreenshot(page,{path: 'screenshots/Adressbuch/18_adressbuch_kontakten_tags_zuweisen.png'});
            await page.keyboard.press('Escape');
            await page.keyboard.press('Escape');
        });

        // @todo error Node is either not visible or not an HTMLElement

        test('test mail', async () => {
            await expectPuppeteer(page).toClick('.x-grid3-row.x-grid3-row-last ', {button: 'right'});
            await page.keyboard.press('Escape');
            await expectPuppeteer(page).toClick('.x-grid3-row.x-grid3-row-first', {button: 'right'});
            felamimailIcon = await expectPuppeteer(page).toMatchElement('.x-menu-item-text', {text: 'Nachricht verfassen'});
            await felamimailIcon.hover();
            await lib.makeScreenshot(page,{path: 'screenshots/Adressbuch/17_adressbuch_email_viele_empfaenger.png'});
        });

        test('send mail', async () => {
            let popupWindow = lib.getNewWindow();
            await felamimailIcon.click();
            popupWindow = await popupWindow
            await popupWindow.waitForFunction(() => !document.querySelector('.ext-el-mask'));
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/Adressbuch/20_adressbuch_email_als_notiz.png'});
            await popupWindow.close();
        })

    });
    describe('treeNodes', () => {
        test('open context menu', async () => {
            if (!(await expectPuppeteer(page).toMatchElement('#Addressbook_Contact_Tree span', {text: 'Meine Adressbücher'}))) {
                await page.click('#Addressbook_Contact_Tree .x-tool.x-tool-toggle');
            }

            for (const el of await page.$$('#Addressbook_Contact_Tree li img.x-tree-elbow-plus')) {
                await el.click()
            }

            await page.waitForSelector('#Addressbook_Contact_Tree span', {
                text: process.env.TEST_USER + 's persönliches Adressbuch'
            });
            await new Promise(r => setTimeout(r, 1000)); // tree animation time

            await expectPuppeteer(page).toClick('#Addressbook_Contact_Tree span', {
                text: process.env.TEST_USER + 's persönliches Adressbuch',
                button: 'right'
            });
            await new Promise(r => setTimeout(r, 1000));
            await page.waitForSelector('.x-menu-item-icon.action_managePermissions');
            await page.hover('.x-menu-item-icon.action_managePermissions');
            await lib.makeScreenshot(page,{path: 'screenshots/StandardBedienhinweise/3_standardbedienhinweise_adresse_berechtigungen.png'});
        });

        test('permissions dialog', async () => {
            try {
                await page.waitForSelector('.x-menu-item-icon.action_managePermissions', {timeout: 100});
            } catch (e) {
                // NOTE: in debug mode screenshot removes focus so menu closes
                await expectPuppeteer(page).toClick('#Addressbook_Contact_Tree span', {
                    text: process.env.TEST_USER + 's persönliches Adressbuch',
                    button: 'right'
                });
            }
            await page.click('.x-menu-item-icon.action_managePermissions');
            await lib.makeScreenshot(page,{path: 'screenshots/StandardBedienhinweise/4_standardbedienhinweise_adressbuch_berechtigungen_verwalten.png'});
            await page.keyboard.press('Escape');
        });
    });


    describe('Edit Contact', () => {
        let popupWindow;
        test('open EditDialog', async () => {
            popupWindow = await lib.getEditDialog('Kontakt hinzufügen');
        });

        test('From Fields', async () => {
            //console.log('Fill fields');
            // @ todo make a array wiht key(n_prefix....) and value -> forech!
            await expectPuppeteer(popupWindow).toMatchElement('input[name=n_prefix]');
            //await new Promise(r => setTimeout(r, 2000));
            //console.log('wait ');
            await expectPuppeteer(popupWindow).toFill('input[name=n_prefix]', 'Dr.');
            await expectPuppeteer(popupWindow).toFill('input[name=n_given]', 'Thomas');
            await expectPuppeteer(popupWindow).toFill('input[name=n_middle]', 'Bernd');
            await expectPuppeteer(popupWindow).toFill('input[name=n_family]', 'Gaurad');
            await expectPuppeteer(popupWindow).toFill('input[name=org_name]', 'DWE');
            await expectPuppeteer(popupWindow).toFill('input[name=org_unit]', 'Personalwesen');
            //await expectPuppeteer(popupWindow).toFill('input[name=title]', 'CEO');
            await expectPuppeteer(popupWindow).toFill('input[name=bday]', '12.03.1956');

            await expectPuppeteer(popupWindow).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text:'Telefon'})
            await expectPuppeteer(popupWindow).toFill('input[class*=x-grid-editor-tel_work]', '0179461021');
            await expectPuppeteer(popupWindow).toClick('.x-grid3-cell-inner.x-grid3-col-name', {text:'Handy'})
            await expectPuppeteer(popupWindow).toFill('input[class*=x-grid-editor-tel_cell]', '0179461021');

            await expectPuppeteer(popupWindow).toFill('input[name=adr_one_postalcode]', '20475');
            await expectPuppeteer(popupWindow).toFill('input[name=adr_one_street]', 'Pickhuben');
            await expectPuppeteer(popupWindow).toFill('input[name=adr_one_locality]', 'Hamburg');
            await expectPuppeteer(popupWindow).toFill('input[name=adr_one_countryname]', 'Deutschland');
            await popupWindow.waitForSelector('.x-combo-list-item');
            await popupWindow.keyboard.down('Enter');
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/Adressbuch/10_adressbuch_kontakt_bearbeiten.png'});
        });

        test('parseAddress', async () => {
            await expectPuppeteer(popupWindow).toMatchElement('button', {text: 'Adresse einlesen'});
            await expectPuppeteer(popupWindow).toClick('button', {text: 'Adresse einlesen'});
            await popupWindow.waitForSelector('.ext-mb-textarea');
            await expectPuppeteer(popupWindow).toFill(
                '.ext-mb-textarea textarea',
                'Max Mustermann Beispielweg 1 354234 Musterdorf !'
            );
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/Adressbuch/11_adressbuch_kontakt_neu_einlesen.png'});
            await expectPuppeteer(popupWindow).toClick('button.btn-close');
        });

        test.skip('add Tag', async () => {
            let arrowtrigger = await popupWindow.$$('.x-form-arrow-trigger');
            // TODO we should not make this dependent on the number of arrow triggers
            await arrowtrigger[10].click();
            await expectPuppeteer(popupWindow).toMatchElement('.x-widget-tag-tagitem-text', {text: 'Elbphilharmonie'});
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/Adressbuch/15_adressbuch_tag_hinzu.png'});
            let btn_text = await popupWindow.$$('.x-btn-text');
            await btn_text[3].click();
            await popupWindow.waitForSelector('.ext-mb-input');
            await expectPuppeteer(popupWindow).toFill('.ext-mb-input', 'Persönlicher Tag');
            await lib.makeScreenshot(popupWindow,{path: 'screenshots/Adressbuch/16_adressbuch_persoenlicher_tag_hinzu.png'});
            await expectPuppeteer(popupWindow).toClick('button', {text: 'Abbrechen'});
        });

        test('save', async () => {
            await expectPuppeteer(popupWindow).toClick('button', {text: 'Ok'});
            await new Promise(r => setTimeout(r, 1000));
        });
    });
});


describe.skip('Group', () => {
    describe('Mainscreen', () => {
        test('go to Mainscreen', async () => {
            await expectPuppeteer(page).toClick('.tine-mainscreen-centerpanel-west span', {text: 'Gruppen'});
            await new Promise(r => setTimeout(r, 500));
            await lib.makeScreenshot(page,{path: 'screenshots/Adressbuch/22_adressbuch_gruppen_uebersicht.png'});
            await lib.makeScreenshot(page,{path: 'screenshots/Adressbuch/23_adressbuch_gruppen_modul.png'});
            //await lib.makeScreenshot(page, {
            //    path: 'screenshots/Adressbuch/23_adressbuch_gruppen_modul.png',
            //    clip: {x: 0, y: 0, width: 150, height: 300}
            //})
        });
    });
});


afterAll(async () => {
    browser.close();
});

async function selectTab(popupWindow, regEx) {
    await expectPuppeteer(popupWindow).toClick('span .x-tab-strip-text', {text: new RegExp(regEx)});
    await new Promise(r => setTimeout(r, 500)); //fix click issue @todo find better way
    await expectPuppeteer(popupWindow).toClick('span .x-tab-strip-text', {text: new RegExp(regEx)});
}
