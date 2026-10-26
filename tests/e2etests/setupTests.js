import pageErrors from './src/lib/pageErrors.js';
import dotenv from 'dotenv';
dotenv.config();
import {red} from 'colorette';

// Let Puppeteer's pageerror handler keep more in-page stack frames (default: 10).
Error.stackTraceLimit = 50;

let startCount = 0;
let testFailed = false;

const envThrowFlag = String(process.env.TEST_THROW_ON_PAGE_ERROR || '').trim().toLowerCase();
const throwOnPageError = ['1', 'true', 'yes', 'on'].includes(envThrowFlag);

const wrapTestFn = (fn) => async function wrapped(...args) {
    try { await fn.apply(this, args); }
    catch (err) { testFailed = true; throw err; }
};
const wrapTest = (testFn) => {
    const wrapped = (...args) => {
        // The test body is the first function argument, wherever it sits:
        // test(fn) | test(name, fn) | test(name, fn, timeout).
        for (let i = 0; i < args.length; i++) {
            if (typeof args[i] === 'function') {
                args[i] = wrapTestFn(args[i]);
                break;
            }
        }
        return testFn.apply(this, args);
    };
    // Keep jest's test.skip / test.only / test.todo / test.concurrent as-is:
    // skipped/todo tests never run a body, and test.only is a local debugging aid.
    for (const k of Object.getOwnPropertyNames(testFn)) {
        if (k === 'length' || k === 'name' || k === 'prototype') continue;
        wrapped[k] = testFn[k];
    }
    return wrapped;
};
global.test = wrapTest(global.test);
if (global.it) global.it = wrapTest(global.it);

global.beforeEach(async () => { startCount = pageErrors.count(); testFailed = false; });

global.afterEach(async () => {
    if (global.browser && testFailed) {
        const name = (expect.getState().currentTestName || 'test').replace(/[^a-z0-9]+/gi, '_');
        const pages = await global.browser.pages();
        for (let i = 1; i < pages.length; i++) {
            await pages[i].screenshot({path: 'screenshots/Error/' + name + i + '.png'});
        }
    }
    if (throwOnPageError && pageErrors.count() > startCount) {
        throw new Error('Uncaught page error(s) during this test:\n' + pageErrors.format());
    }
});

global.afterAll(async () => {
    if (pageErrors.count() > 0) {
        const message = '\n\n' +
            '========== PAGE ERROR SUMMARY ============\n\n' +
            'Uncaught page error(s) in this file' +
            (throwOnPageError ? ' (tests after the first were aborted)' : '') + ':\n' +
            pageErrors.format() + '\n\n' +
            '==========================================\n\n';
        console.log(red(message));
    }
});