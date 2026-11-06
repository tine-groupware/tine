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

## Debugging Tests in a Shared Browser

Tests can run in an already running Chrome that the agent also controls via the Claude in Chrome
extension (`mcp__claude-in-chrome__*`). Setup for the user is described in `tests/e2etests/README.md`
("Debug in a Running Browser"): a separate Chrome instance with its own `--user-data-dir` and
`--remote-debugging-port=9222`, with the extension installed there.

Workflow:

1. **Check the debug port:** `curl -s http://127.0.0.1:9222/json/version`. If it does not answer, ask the user to start the instance (command in README).
2. **Use the right extension instance:** several browsers may be connected (e.g. the user's normal Chrome). Verify by opening a tab via the extension and checking it appears in `node scripts/cdp-inspect.js list`. If not, let the user pick with `switch_browser`.
3. **Open the test tab first:** navigate an extension tab to `TEST_URL`. `getConnectedPage()` reuses the first tab whose URL starts with `TEST_URL`; otherwise the test opens a new tab outside the extension's tab group, which the agent can't see.
4. **Run the test** with `TEST_BROWSER_URL=http://127.0.0.1:9222` (set in `.env` or inline). The test clears cookies/storage of `TEST_URL`, logs in and only disconnects at the end, so the final state stays visible.
5. **Inspect:** main tab via the extension (screenshot, console, `javascript_tool` for `Tine`/`Ext` state). Popup windows (edit dialogs) are not in the tab group, so use `node scripts/cdp-inspect.js screenshot all` / `eval <index> '<js>'`.

Pitfalls:

- **Node version:** puppeteer 25 needs Node >= 22.12. The agent shell does not load nvm, so call it via path, e.g. `PATH=$HOME/.nvm/versions/node/v24.<x>/bin:$PATH npm test <file>` (`ls ~/.nvm/versions/node`).
- **MFA:** a login stuck in a FIDO2/WebAuthn dialog means MFA is active for the test user; ask the user to disable it.
- Keep the test tab open after the run, so the next run can reuse it.

## Real Dialogs Only

E2E tests verify what a user sees and does. They must always use the real UI:

- **Open real dialogs** the way a user does (toolbar button, context menu, double click), e.g. `getEditDialog('Termin hinzufügen')`. Never build components, windows or grids in the page (`new Ext.Window(...)`, `createApp(...)`) to test a widget in isolation.
- **Interact like a user**: click, type, press keys, drag (e.g. resize a window via its resize handle). Never call component methods to change UI state (`setValue`, `setWidth`, `onTriggerClick`, `select`, ...) and never manipulate component internals (e.g. Vue `setupState`, `__vueParentComponent`).
- **Assert on the rendered result** (DOM, computed styles, field values shown to the user). Reading state for assertions is fine, changing it is not.
- **Test data via API is allowed** as setup / teardown (e.g. create records with `Tine.<App>.save<Model>()` in `beforeAll` and delete them in `afterAll`), as well as user preferences the test depends on (e.g. `windowtype`). Always restore changed data.
- **Don't rely on the current state of the test user** (preferences, saved window sizes, data left by other runs). Dialogs open as browser popups by default (preference `windowtype` = `autodetect`), so use `getEditDialog()`. If a test needs Ext windows, it sets `windowtype` itself (save it and update the registry, the client reloads itself) and restores the original value in `afterAll`.
- If a feature can't be reached through the real UI (e.g. missing test data like a contact image), create that data via API or upload it via the UI instead of faking it in the DOM.
- Unit-level behaviour (pure logic, edge cases) belongs into Jest unit tests (`tests/js/jest`), not into E2E tests.

## Core Principles

1. **No Hardcoded Delays**: Eliminate `setTimeout`. Use dynamic waiting (`waitForSelector`, `waitForFunction`).
2. **Structured Form Interactions**: Use `formFillInputField` and `formFillComboField` from `browser.helpers.js`. Never `page.type`/`page.fill`.
3. **Robust Assertions**: Replace `toMatchElement` with `waitForSelector` + `page.evaluate` text extraction. Normalize whitespace.
4. **Dialog Handling**: Use `getEditDialog(btnText)` for popups. Handles loading masks and content readiness.
5. **Simplify**: Prefer minimal, readable waiting logic over over-engineered helpers.
6. **Use the library first**: Check `src/lib/browser.js` before writing own `waitForFunction` or navigation code (e.g. `waitForElement` with `{hidden: true}`, `getBrowser(app, module)`).
6. **Real Dialogs Only**: Test through real dialogs and user interactions, no DOM tricks (see above).

## Library Reference

| Function | Purpose |
|----------|---------|
| `browser.getBrowser(app, module)` | Initialize browser, login, navigate to app/module |
| `browser.getEditDialog(btnText, page)` | Click button, wait for popup, handle loading masks |
| `browser.formFillInputField(page, selector, value)` | Fill input with typing delay simulation |
| `browser.formFillComboField(page, selector, value)` | Fill combo box with typing delay and selection |
| `browser.waitForAppReady(page)` | Wait for loading indicators to disappear |
| `browser.waitForElement(page, selector, {visible\|hidden, timeout})` | Wait until all matches are visible / hidden (or exist) |
| `browser.answerMessageBox(page, button, {title})` | Answer a message box and wait until it is closed |
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
