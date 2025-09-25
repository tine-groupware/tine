const expect = require('expect-puppeteer')
const lib = require('../../lib/browser')
require('dotenv').config()
let subject

beforeAll(async () => {
  await lib.getBrowser('E-Mail')
  await page.waitForSelector('a span',{text: "Posteingang"})
  await expect(page).toClick('a span',{text: "Posteingang"})
  await page.waitForTimeout(2000)
})

describe('test email rendering', () => {
  test ('import test emails', async () => {
    for (let i=1; i<=4; i++) {
      let fileToUpload = 'src/test/Felamimail/Test '+i+'.eml'

      await expect(page).toMatchElement('.x-btn-text', {text: 'Nachrichten importieren'});
      await page.waitForTimeout(100); // wait for btn to get active
      await expect(page).toClick('.x-btn-text', {text: 'Nachrichten importieren'});

      let elementHandle = await page.$("input[type=file]");
      await elementHandle.uploadFile(fileToUpload);

      try {
        // There might be a pop-up telling us that only .eml is allowed for no reason
        await page.waitForSelector('button.vue-button.ok-button', {timeout: 2000})
        await expect(page.toClick('button.vue-button.ok-button'))
      } catch (e) {}
      await page.waitForSelector('.x-grid3-col-subject', {text: 'Test '+i})
    }
  })

  for (let i=1; i<=4; i++) {
    test('light/dark mode email '+i, async () => {
      await openEmailPreview('Test '+i)
      await lib.makeScreenshot(page,{path: 'screenshots/Felamimail/preview-'+i+'.png'})
    })
  }

  for (let i=1; i<=4; i++) {
    test('light/dark mode email '+i+' response', async () => {
      await openEmailResponse('Test '+i, 'response-'+i+'.png')
    })
  }

  test('delete emails', async  () => {
    for (let i=1; i<=4; i++) {
      let title = 'Test ' + i
      await page.waitForSelector('.x-grid3-col-subject', {text: title})
      expect(page).toClick('.x-grid3-col-subject', {text: title})

      await waitForLoadmask(page)

      await page.waitForSelector('.x-btn-text', {text: 'Löschen'})
      await expect(page).toClick('.x-btn-text', {text: 'Löschen'});

      await waitForLoadmask(page)
    }
  })
});

afterAll(async () => {
  browser.close()
});

async function openEmailPreview (title){
  await page.waitForSelector('.x-grid3-col-subject', {text: title})
  expect(page).toClick('.x-grid3-col-subject', {text: title})

  await waitForLoadmask(page)

  await page.waitForSelector('div.preview-panel-felamimail-headers > div:nth-child(1) > div.preview-panel-felamimail-header-row-right', {text: title, timeout: 2000})
  await page.waitForSelector('.preview-panel-felamimail-body')
}

async function openEmailResponse (title, filename){
  await page.waitForSelector('.x-grid3-col-subject', {text: title})
  expect(page).toClick('.x-grid3-col-subject', {text: title})
  let newPage = await lib.getEditDialog('Antworten')

  await waitForLoadmask(newPage)

  await newPage.waitForSelector('input[name="subject"]')
  await lib.makeScreenshot(newPage,{path: 'screenshots/Felamimail/'+filename})
  await newPage.close()
}

async function waitForLoadmask (p){
  try {
    await p.waitForSelector('.ext-el-mask', {timeout: 2000})
  } catch {}
  await p.waitForFunction(() => !document.querySelector('.ext-el-mask'))
}
