import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

beforeAll(async () => {
  await lib.getBrowser('E-Mail')
  await page.waitForSelector('a span',{text: "Posteingang"})
  await expectPuppeteer(page).toClick('a span',{text: "Posteingang"})
  await new Promise(r => setTimeout(r, 2000))
})

describe('test email rendering', () => {
  test ('import test emails', async () => {
    for (let i=1; i<=4; i++) {
      let fileToUpload = 'src/test/Felamimail/Test '+i+'.eml'

      await expectPuppeteer(page).toMatchElement('.t-app-felamimail .x-toolbar-left-row .x-btn-text', {text: 'Nachrichten importieren'});

      const selector = '.t-app-felamimail .x-toolbar-left-row input[type="file"]';
      const fileInput = await page.waitForSelector(selector, {timeout: 2000});
      if (!fileInput) {
        throw new Error(`File input not found: ${selector}`);
      }
      await fileInput.uploadFile(fileToUpload);

      try {
        // There might be a pop-up telling us that only .eml is allowed for no reason
        await page.waitForSelector('button.vue-button.ok-button', {timeout: 2000})
        await expectPuppeteer(page.toClick('button.vue-button.ok-button'))
      } catch (e) {}
      await expectPuppeteer(page).toMatchElement('.t-app-felamimail .x-grid3-col-subject', {text: 'Test ' + i});
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
      await lib.clickOnElementWithLabel(page, '.t-app-felamimail .x-grid3-col-subject', title);

      await waitForLoadmask(page)

      await lib.clickOnElementWithLabel(page, '.t-app-felamimail .x-btn-text', 'Löschen');

      await waitForLoadmask(page)
    }
  })
});

afterAll(async () => {
  await lib.closeBrowser();
});

async function openEmailPreview (title){
  await lib.clickOnElementWithLabel(page, '.t-app-felamimail .x-grid3-col-subject', title);

  await page.waitForSelector('.x-border-panel .x-panel:not(.x-hide-display) .email-stapled-container');

  await page.waitForSelector('div.preview-panel-felamimail-headers > div:nth-child(1) > div.preview-panel-felamimail-header-row-right', {text: title, timeout: 2000})
  await page.waitForSelector('.preview-panel-felamimail-body')
}

async function openEmailResponse (title, filename){
  await lib.clickOnElementWithLabel(page, '.t-app-felamimail .x-grid3-col-subject', title);
  let newPage = await lib.getEditDialog('Antworten')

  await waitForLoadmask(newPage)

  await newPage.waitForSelector('input[name="subject"]')
  await lib.makeScreenshot(newPage,{path: 'screenshots/Felamimail/'+filename})
  // TODO: Add wrapper function to helpers lib.
  await newPage.close();
  await page.bringToFront();
  await waitForLoadmask(page);
}

async function waitForLoadmask(p) {
  const maskSelectors = ['.tine-viewport-waitcycle', '.ext-el-mask', '.x-mask-loading'];
  const timeout = lib.getEnvInt('TEST_TIMEOUT_MASK');

  await p.waitForFunction(
    (masks) => {
      const isSelectorVisible = (sel) => {
        const el = document.querySelector(sel);
        if (!el) return false;
        const style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      };
      return masks.every((sel) => !isSelectorVisible(sel));
    },
    {timeout},
    maskSelectors,
    );
}
