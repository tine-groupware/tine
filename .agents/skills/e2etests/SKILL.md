# E2E Test Skill

## Purpose

Guide agents in refactoring legacy E2E tests to match stability, waiting logic, and text-handling patterns established in refactored examples. Also provides general context for writing and maintaining E2E tests.

## Activation

Auto-invoke when working on `tests/e2etests/src/**/*.test.js` files or discussing E2E test stability, waiting logic, or assertion patterns.

## Project Context

E2E tests for tine use Jest + Puppeteer to verify workflows from the end-user's perspective. Tests run against a live tine instance configured via `.env` (TEST_URL, TEST_USERNAME, TEST_PASSWORD).

### Directory Structure

```
tests/e2etests/
├── src/
│   ├── lib/browser.js              # Main entry: getBrowser, getEditDialog, formFill*, etc.
│   ├── lib/browser.helpers.js      # Waiting logic, throttling, login, form fillers, screenshots
│   ├── test/                       # Functional tests by app module
│   └── testScreenshots/            # Screenshot-based tests
├── .env                            # Configuration
├── jest.config.mjs                 # Jest configuration
└── setupTests.js                   # Global test setup
```

### Running Tests

```bash
cd tests/e2etests
npm test                          # all tests
npm test src/test/Addressbook/Addressbook.test.js  # single file
```

## Core Principles

1. **No Hardcoded Delays**: Eliminate `setTimeout`. Use dynamic waiting (`waitForSelector`, `waitForFunction`).
2. **Structured Form Interactions**: Use `formFillInputField` and `formFillComboField` from `browser.helpers.js`. Never `page.type`/`page.fill`.
3. **Robust Assertions**: Replace `toMatchElement` with `waitForSelector` + `page.evaluate` text extraction. Normalize whitespace.
4. **Dialog Handling**: Use `getEditDialog(btnText)` for popups. Handles loading masks and content readiness.
5. **Simplify**: Prefer minimal, readable waiting logic over over-engineered helpers.

## Library Reference

| Function | Purpose |
|----------|---------|
| `browser.getBrowser(app, module)` | Initialize browser, login, navigate to app/module |
| `browser.getEditDialog(btnText, page)` | Click button, wait for popup, handle loading masks |
| `browser.formFillInputField(page, selector, value)` | Fill input with typing delay simulation |
| `browser.formFillComboField(page, selector, value)` | Fill combo box with typing delay and selection |
| `browser.waitForAppReady(page)` | Wait for loading indicators to disappear |
| `browser.getCurrentUser(page)` | Get logged-in user account info |

## Pattern Mapping

| Legacy | Refactored |
|--------|------------|
| `await new Promise(r => setTimeout(r, 500))` | `await page.waitForSelector('.ready-indicator', {visible: true})` |
| `await page.type('#field', 'text')` | `await browser.formFillInputField(page, '#field', 'text')` |
| `await expect(page).toMatchElement('div', {text: 'Label'})` | `await page.waitForSelector('.label-selector', {visible: true})` + text extraction |
| Manual popup waiting | `await browser.getEditDialog('Button Text')` |

## Refactoring Checklist

- [ ] Remove all `setTimeout` calls
- [ ] Replace `page.type`/`page.fill` with helper functions
- [ ] Replace `toMatchElement` with `waitForSelector` + text extraction
- [ ] Use `getEditDialog` for popup interactions
- [ ] Validate test runs successfully after changes
- [ ] Document any app-specific quirks or selectors

## Additional Resources

- Full agent guidance: [`tests/e2etests/AGENTS.md`](../../tests/e2etests/AGENTS.md)
- User-facing guide (setup, running, writing tests): [`tests/e2etests/README.md`](../../tests/e2etests/README.md)
