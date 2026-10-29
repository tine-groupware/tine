import {expect, expect as expectPuppeteer} from 'expect-puppeteer';
import lib from '../lib/browser.js';

beforeAll(async () => {
    await lib.getBrowser('Lesezeichen');
});

describe('monitor bookmark targets', () => {

    const bookmarksConfig = lib.getEnvJson('MONITOR_BOOKMARKS_JSON');
    if (!bookmarksConfig.targets) {
        throw new Error('Error: Invalid config in MONITOR_BOOKMARKS_JSON');
    }

    bookmarksConfig.targets.forEach(target => {

        test(`open bookmark: ${target.name}`, async () => {

            const targetPage = await findAndOpenBookmarkPage(target.name, target.timeout);
            let pageURL;
            let pageTitle;
            let pageBody;

            // Check if URL starts as expected.
            if (target.urlStartsWith) {
                try {
                    pageURL = targetPage.url();
                    const startsWithEscaped = escapeRegExp(target.urlStartsWith);
                    expectPuppeteer(pageURL).toMatch(new RegExp(`^${startsWithEscaped}`));
                } catch (error) {
                    throw new Error(`Error: Invalid URL | Bookmark: ${target.name} | URL: ${pageURL} | Expected URL: ${target.urlStartsWith}`);
                }
            }

            // Check if title is present.
            try {
                pageTitle = await targetPage.title();
                expectPuppeteer(pageTitle).not.toEqual('');
            } catch (error) {
                throw new Error(`Error: Empty Title | Bookmark: ${target.name} | URL: ${pageURL}`);
            }

            // Check if body is present.
            try {
                pageBody = await targetPage.evaluate(() => {
                    const el = document.querySelector('body');
                    return !!el ? el.innerHTML : null;
                });
                expectPuppeteer(pageBody).not.toEqual('');
            } catch (error) {
                throw new Error(`Error: Empty Body | Bookmark: ${target.name} | URL: ${pageURL}`);
            }

            // Compare selector on target page.
            if (target.selector) {
                try {
                    await targetPage.waitForSelector(target.selector, {timeout: lib.getEnvInt('TEST_TIMEOUT_ACTIONABLE')});
                } catch (error) {
                    throw new Error(`Error: Selector Not Found | Bookmark: ${target.name} | URL: ${pageURL} | Selector: ${target.selector}`);
                }
            }

            await targetPage.close();
        });
    });
});

/**
 * Searches for a given Bookmark name, double-clicks on the search result and returns
 * the page object of the new window the Bookmark link is opened in.
 *
 * @param {string} name - Bookmark name.
 * @param {number} timeout - Timeout in milliseconds.
 * @returns {puppeteer.Page}
 */
async function findAndOpenBookmarkPage(name, timeout) {
    // Search for Bookmark with given name.
    const inputOptions = {selector: '.t-app-bookmarks .x-toolbar-right-row .x-form-text.x-form-field', value: name, typingDelay: 10};
    await lib.formFillInputField(global.page, inputOptions);
    await lib.clickOnElement(global.page, '.t-app-bookmarks .x-form-search-trigger');

    const gridItemSelector = '.t-app-bookmarks .x-grid3-col-name span';
    await lib.refreshGridAndWaitForReady(global.page, '.t-app-bookmarks');

    // Double-click on found item and wait for the target page to be loaded.
    // Note: Don't use lib.getEditDialog() because it expects a tine popup.
    const targetPagePromise = lib.getNewWindow(lib.getEnvInt('MONITOR_TIMEOUT_POPUP_OPEN'));
    targetPagePromise.catch(() => {});

    // Record the start time before clicking the bookmark link.
    const clickTime = Date.now();
    await lib.clickOnElementWithLabel(global.page, gridItemSelector, name, {clickCount: 2});
    const targetPage = await targetPagePromise;
    await targetPage.waitForNetworkIdle({timeout: lib.getEnvInt('MONITOR_TIMEOUT_NETWORK_TIMEOUT'), idleTime: lib.getEnvInt('MONITOR_TIMEOUT_NETWORK_IDLE')});

    // Check if loading exceeded the configured timeout.
    const loadTime = Date.now() - clickTime;
    if (timeout > 0 && loadTime > timeout) {
        throw new Error(`Error: Timeout Loading Page | Bookmark: ${name} | URL: ${targetPage.url()} | Load Time: ${loadTime}ms | Timeout: ${timeout}ms`);
    }

    return targetPage;
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

afterAll(async () => {
    await lib.closeBrowser();
});
