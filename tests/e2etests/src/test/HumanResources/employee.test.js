const expectPuppeteer = require('expect-puppeteer');
const lib = require('../../lib/browser');

require('dotenv').config();

beforeAll(async () => {
    await lib.getBrowser('Human Resources');
});

describe.skip('employee', () => {
    describe('employee grid', () => {
        test('show grid', async () => {
            await expectPuppeteer(page).toClick('.x-tree-node span', {text: 'Mitarbeiter', visible: true});
            await expectPuppeteer(page).toMatchElement('.x-grid3-hd-account_id');
        });

        test('select employee', async () => {
            await expectPuppeteer(page).toClick('.x-grid3-col-account_id', {text: 'James McBlack'});
        });
    });
    
    describe('edit dialog', () => {
        let employeeEditDialog
        test('open dialog', async () => {
            employeeEditDialog = await lib.getEditDialog('Mitarbeitende bearbeiten');
        });
    
        describe('vacation (freetime)', () => {
            const testString = 'test vacation ' + Math.round(Math.random() * 10000000);
            test('vacation grid', async () => {
                await expectPuppeteer(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Urlaub'});
            });
    
            describe('add vacation', () => {
                let freetimeEditDialog;
                test('open dialog', async() => {
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    freetimeEditDialog = await lib.getEditDialog('Urlaubstage hinzufügen', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(2000);
                    try {
                        await freetimeEditDialog.waitForFunction(() => document.querySelector('.ext-el-mask-msg.x-mask-loading div').textContent === 'Übertrage Abwesenheit...');
                    } catch {}

                    await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask-msg.x-mask-loading div'));
                    await expectPuppeteer(freetimeEditDialog).toFill('textarea[name=description]', testString);

                    const inputValue = await freetimeEditDialog.evaluate(() => document.querySelector('input[name=employee_id]').value);
                    const namePart = inputValue.split(' ').slice(1).join(' ');
                    expect(namePart).toEqual('James McBlack');
                    expect(await freetimeEditDialog.evaluate(() => document.querySelector('input[name=type]').value)).toEqual('[U] Urlaub');
                },300000);

                test('exclude days (a sunday) are loaded and applied', async () => {
                        await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(8).x-date-disabled');
                });
                
                test('dates can be selected', async () => {
                    let remainingDays = await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value);
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(4) > a > em > span');
                    await freetimeEditDialog.waitForTimeout(50);
                    
                    if (remainingDays-2 !== await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value)) {
                        throw new Error('remaining days do not decrease');
                    }
                });

                test('dates can be deselected', async () => {
                    let remainingDays = await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value);

                    await expectPuppeteer(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await freetimeEditDialog.waitForTimeout(50);
                    
                    if (remainingDays+1 !== await freetimeEditDialog.evaluate(() => +document.querySelector('input[name=scheduled_remaining_vacation_days]').value)) {
                        throw new Error('remaining days do not increase');
                    }
                });
                
                test('vacation is saved', async () => {
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});
                    await freetimeEditDialog.waitForTimeout(15000);

                    // wait for loading starts and ends
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');

                    await employeeEditDialog.waitForTimeout(1000);

                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-grid3-cell-inner.x-grid3-col-type', {text: '[U] Urlaub'});
                });
            });

            describe('updated vacation', () => {
                let freetimeEditDialog;
                test('load vacation', async () => {
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    freetimeEditDialog = await lib.getEditDialog('Urlaubstage bearbeiten', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(1000);
                    try {
                        await freetimeEditDialog.waitForFunction(() => document.querySelector('.ext-el-mask-msg.x-mask-loading div').textContent === 'Übertrage Abwesenheit...');
                    } catch {}
                    await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask-msg.x-mask-loading div'));
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(4).x-date-selected');
                });

                test('vacation can be updated', async () => {
                    // feastAndFreeDays loaded/applied
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(7).x-date-disabled');
                    
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(2) > a > em > span');
                    await expectPuppeteer(freetimeEditDialog).toFill('textarea[name=description]', testString + ' update');

                    await expectPuppeteer(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});

                    try {
                        await freetimeEditDialog.waitForSelector('.ext-el-mask', {timeout: 5000});
                        await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask'));
                    } catch {}

                    // wait for loading starts and ends
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');


                    await employeeEditDialog.waitForTimeout(2000); // wait till view is updated

                    const days = await employeeEditDialog.$eval('.tine-hr-freetimegrid-type-VACATION .x-grid3-cell-inner.x-grid3-col-days_count', el=> el.textContent);
                    
                    if (days !='2') {
                        throw new Error('days count mismatch' + days);
                    }
                });
            });

            describe('delete vacation', () => {
                test('confirm dialog is shown', async () => {
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-grid3-col-type', {text: '[U] Urlaub'});
                    await expectPuppeteer(employeeEditDialog).toClick('button', {text: 'Urlaubstage löschen'});
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.btn.btn-secondary.btn-md.mx-1.x-tool-close.vue-button.yes-button');
                    await expectPuppeteer(employeeEditDialog).toClick('.btn.btn-secondary.btn-md.mx-1.x-tool-close.vue-button.yes-button');
                    await employeeEditDialog.waitForTimeout(2000);
                });
                
                test('vacation is deleted', async () => {
                    // wait for loading starts and ends
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).not.toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-grid3-col-type', {text: '[U] Urlaub'});
                });
            });
        });

        describe('sickness (freetime)', () => {
            const testString = 'test sickness ' + Math.round(Math.random() * 10000000);
            test('sickness grid', async () => {
                await expectPuppeteer(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Krankheit'});
                await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled');
            });

            describe('add sickness', () => {
                let freetimeEditDialog;
                test('open dialog', async() => {
                    freetimeEditDialog = await lib.getEditDialog('Krankheitstage hinzufügen', employeeEditDialog);
                    await freetimeEditDialog.waitForTimeout(1000);
                    await expectPuppeteer(freetimeEditDialog).toFill('textarea[name=description]', testString);
                });

                test('type is resolved', async () => {
                    if ('[K] Krankheit' !== await freetimeEditDialog.evaluate(() => document.querySelector('input[name=type]').value)) {
                        return Promise.reject('type not resolved');
                    }
                });

                test('exclude days (a sunday) are loaded and applied', async () => {
                    await freetimeEditDialog.waitForSelector('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(3) > td:nth-child(7).x-date-disabled');
                });

                test('status can be set', async () => {
                    await expectPuppeteer(freetimeEditDialog).toClick('input[name=type_status]');
                    await freetimeEditDialog.waitForSelector('.x-combo-list-item')
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-combo-list-item', {text: 'Unentschuldigt'});
                });
                
                test('dates can be selected', async () => {
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(2) > td:nth-child(2) > a > em > span');
                });
                
                test('sickness is saved', async () => {
                    await expectPuppeteer(freetimeEditDialog).toClick('.x-toolbar-right-row button', {text: 'Ok'});

                    try {
                        await freetimeEditDialog.waitForSelector('.ext-el-mask', {timeout: 5000});
                        await freetimeEditDialog.waitForFunction(() => !document.querySelector('.ext-el-mask'));
                    } catch {}

                    // wait for loading starts and ends
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-SICKNESS .x-ux-pagingtb-refresh-disabled');

                    await employeeEditDialog.waitForTimeout(5000);

                    await employeeEditDialog.waitForSelector('.tine-hr-freetimegrid-type-SICKNESS .x-grid3-cell-inner.x-grid3-col-type_status');

                    const status = await employeeEditDialog.$eval('.tine-hr-freetimegrid-type-SICKNESS .x-grid3-cell-inner.x-grid3-col-type_status', el=> el.textContent);

                    if (status !='Unentschuldigt') {
                        throw new Error('days count mismatch');
                    }

                    await lib.makeScreenshot(employeeEditDialog,{path: 'screenshots/HumanResources/13_humanresources_mitarbeiter_krankheit.png'});

                }, 300000);
            });

            describe('book sickness as vacation', () => {
                test('can book sickness as vacation', async () => {
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-SICKNESS .x-grid3-cell-inner.x-grid3-col-type_status', {button: 'right'});
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).toClick('.x-menu-item-text', {text: 'Als Urlaub buchen'});
                    await employeeEditDialog.waitForTimeout(2000);
                });
                test('sickness got vacation', async () => {
                    await expectPuppeteer(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Urlaub'});
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled', {timeout: 10000});
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-grid3-cell-inner.x-grid3-col-type', {text: '[U] Urlaub'});
                    await employeeEditDialog.waitForTimeout(2000);
                });
            });
            
            describe('delete vacation (was sickness)', () => {
                test('confirm dialog is shown', async () => {
                    await employeeEditDialog.waitForTimeout(2000);
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-grid3-cell-inner.x-grid3-col-type', {text: '[U] Urlaub'});
                    await expectPuppeteer(employeeEditDialog).toClick('button', {text: 'Urlaubstage löschen'});
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.btn.btn-secondary.btn-md.mx-1.x-tool-close.vue-button.yes-button');
                    await expectPuppeteer(employeeEditDialog).toClick('.btn.btn-secondary.btn-md.mx-1.x-tool-close.vue-button.yes-button');

                });

                test('vacation is deleted', async () => {
                    // wait for loading starts and ends
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toClick('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled.x-item-disabled');
                    await expectPuppeteer(employeeEditDialog).toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-ux-pagingtb-refresh-disabled');

                    await expectPuppeteer(employeeEditDialog).not.toMatchElement('.tine-hr-freetimegrid-type-VACATION .x-grid3-cell-inner.x-grid3-col-type', {text: '[U] Urlaub'});
                    await expectPuppeteer(employeeEditDialog).toClick('button', {text: 'Ok'});
                });
            });
        });
    });
    describe.skip('edit contract', () => {
        let employeeEditDialog, contractEditDialog
        test('edit dialog', async () => {
            await page.waitForTimeout(1000);
            await expectPuppeteer(page).toClick('.x-grid3-col-account_id', {text: 'Paul Wulf'});
            await page.waitForTimeout(1000);
            employeeEditDialog = await lib.getEditDialog('Mitarbeitende bearbeiten');
            await employeeEditDialog.waitForTimeout(3000);
            await expectPuppeteer(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Verträge', visible:true});
        });
        test('open contract', async () => {
            await employeeEditDialog.waitForTimeout(5000)
            await employeeEditDialog.waitForSelector('.x-grid3-cell-first .x-grid3-cell-inner.x-grid3-col-start_date');
            await employeeEditDialog.click('.x-grid3-cell-first .x-grid3-cell-inner.x-grid3-col-start_date');
            await employeeEditDialog.waitForTimeout(5000);
            await expectPuppeteer(employeeEditDialog).toClick('button', {text: 'Vertrag löschen'});
            await employeeEditDialog.waitForTimeout(1000);
            await expectPuppeteer(employeeEditDialog).toClick('.btn-content', {text: 'Ja'});
            await employeeEditDialog.waitForTimeout(2000);
            contractEditDialog = await lib.getEditDialog('Vertrag hinzufügen', employeeEditDialog);
            await contractEditDialog.waitForTimeout(2000);
        });
        test('edit contract', async () => {
            await contractEditDialog.click('input[name=start_date]');
            await contractEditDialog.click('.x-form-field-wrap.x-form-field-trigger-wrap.x-form-invalid.x-trigger-wrap-focus .x-form-trigger');
            await expectPuppeteer(contractEditDialog).toClick('.x-date-picker table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(2) > td:nth-child(1) > a > em > span');
            await contractEditDialog.waitForTimeout(1000);
            await contractEditDialog.type('input[name=feast_calendar_id]', 'Feast');
            await expectPuppeteer(contractEditDialog).toClick('.x-combo-list-item', {text: 'Feast Calendar'});
            await contractEditDialog.waitForTimeout(2000);
            await expectPuppeteer(contractEditDialog).toFill('input[name=weekdays_0]', '06:00', {delay: 50});
            await contractEditDialog.waitForTimeout(200);
            await expectPuppeteer(contractEditDialog).toFill('input[name=weekdays_1]', '08:00', {delay: 50});
            await contractEditDialog.waitForTimeout(200);
            await expectPuppeteer(contractEditDialog).toFill('input[name=weekdays_2]', '03:00', {delay: 50});
            await contractEditDialog.waitForTimeout(200);
            await expectPuppeteer(contractEditDialog).toFill('input[name=weekdays_3]', '00:00', {delay: 50});
            await contractEditDialog.waitForTimeout(200);
            await expectPuppeteer(contractEditDialog).toFill('input[name=weekdays_4]', '04:00', {delay: 50});
            await contractEditDialog.waitForTimeout(200);
        });
        test('edit breaktime configs', async () => {
            await contractEditDialog.click('[class=new-row] input');
            let breaktime = lib.getNewWindow();
            await contractEditDialog.waitForTimeout(1000);
            await expectPuppeteer(contractEditDialog).toMatchElement('.x-combo-list-item', {
                text: 'Pausenzeit',
                visible: true
            });
            await expectPuppeteer(contractEditDialog).toClick('.x-combo-list-item', {
                text: 'Pausenzeit',
                visible: true
            });
            breaktime = await breaktime;
            await breaktime.waitForTimeout(1000);
            await expectPuppeteer(breaktime).toFill('input[name=time_worked]', '06:00', {delay: 50});
            await expectPuppeteer(breaktime).toFill('input[name=break_time]', '01:00', {delay: 50});
            await expectPuppeteer(breaktime).toClick('button', {text: 'Ok'});
            await contractEditDialog.waitForTimeout(5000);
        });
        test('edit workingtimeschema', async () => {
            await contractEditDialog.type('[class=new-row] input', 'Arbeitszeitlimitierung');
            await contractEditDialog.waitForTimeout(1000);
            let workingtimeschema = lib.getNewWindow();
            await expectPuppeteer(contractEditDialog).toMatchElement('.x-combo-list-item', {
                text: 'Arbeitszeitlimitierung',
                visible: true
            });
            await expectPuppeteer(contractEditDialog).toClick('.x-combo-list-item', {
                text: 'Arbeitszeitlimitierung',
                visible: true
            });
            workingtimeschema = await workingtimeschema;
            await workingtimeschema.waitForTimeout(1000);
            await expectPuppeteer(workingtimeschema).toFill('input[name=start_time]', '06:00', {delay: 50});
            await expectPuppeteer(workingtimeschema).toFill('input[name=end_time]', '20:00', {delay: 50});
            await expectPuppeteer(workingtimeschema).toClick('button', {text: 'Ok'});
            await contractEditDialog.waitForTimeout(1000);
        });
        test('edit workingtimeconverter', async () => {
            /*await contractEditDialog.type('[class=new-row] input', 'Arbeitszeitumwandler');
            await contractEditDialog.waitForTimeout(1000);
            let WorkingtimeConverter = lib.getNewWindow();
            await expectPuppeteer(contractEditDialog).toMatchElement('.x-combo-list-item', {text: 'Arbeitszeitumwandler', visible:true});
            await expectPuppeteer(contractEditDialog).toClick('.x-combo-list-item', {text: 'Arbeitszeitumwandler', visible:true});
            WorkingtimeConverter = await WorkingtimeConverter;
            await WorkingtimeConverter.waitForTimeout(2000);
            await expectPuppeteer(WorkingtimeConverter).toClick('button', {text: 'Ok'});
            await contractEditDialog.waitForTimeout(2000);*/
            await expectPuppeteer(contractEditDialog).toClick('button', {text: 'Ok'});
            await employeeEditDialog.waitForTimeout(2000);
            await expectPuppeteer(employeeEditDialog).toClick('button', {text: 'Ok'});
            await page.waitForTimeout(2000);
        },);

        test('check contract', async () => {
            await page.waitForTimeout(2000);
            await expectPuppeteer(page).toClick('.x-grid3-col-account_id', {text: 'Paul Wulf'});
            await page.waitForTimeout(2000);
            employeeEditDialog = await lib.getEditDialog('Mitarbeitende bearbeiten');
            await expectPuppeteer(employeeEditDialog).toClick('.x-tab-strip-text', {text: 'Verträge'});
            await employeeEditDialog.waitForTimeout(5000)
            await employeeEditDialog.waitForSelector('.x-grid3-cell-first .x-grid3-cell-inner.x-grid3-col-start_date');
            await employeeEditDialog.click('.x-grid3-cell-first .x-grid3-cell-inner.x-grid3-col-start_date');
            await employeeEditDialog.waitForTimeout(5000);
            contractEditDialog = await lib.getEditDialog('Vertrag bearbeiten', employeeEditDialog);
            await contractEditDialog.waitForTimeout(5000);
            expect(await contractEditDialog.evaluate(() => document.querySelector('input[name=weekdays_0]').value)).toEqual('06:00');
            expect(await contractEditDialog.evaluate(() => document.querySelector('input[name=weekdays_1]').value)).toEqual('08:00');
            expect(await contractEditDialog.evaluate(() => document.querySelector('input[name=weekdays_2]').value)).toEqual('03:00');
            expect(await contractEditDialog.evaluate(() => document.querySelector('input[name=weekdays_3]').value)).toEqual('00:00');
            expect(await contractEditDialog.evaluate(() => document.querySelector('input[name=weekdays_4]').value)).toEqual('04:00');
            expect(await contractEditDialog.evaluate(() => document.querySelector('.x-grid3-body div:nth-child(1) .x-grid3-col-configRecord').textContent))
                .toEqual(' Wenn 06:00 Arbeitszeit überschritten sind, werden 01:00 Pausenzeit automatisch abgezogen. (Pausenzeit)');
            expect(await contractEditDialog.evaluate(() => document.querySelector('.x-grid3-body div:nth-child(2) .x-grid3-col-configRecord').textContent))
                .toEqual(' Die Arbeitszeit wird von 06:00 bis 20:00 ausgewertet. (Arbeitszeitlimitierung)');

        })
    });
});

afterAll(async () => {
    browser.close();
});
