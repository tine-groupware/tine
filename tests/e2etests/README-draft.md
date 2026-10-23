# E2E Tests — Stability, Debugging & Error Handling

> Draft section for `tests/e2etests/README.md`. Documents the error-handling and
> debugging capabilities of the E2E framework.

## Page Error Handling

Uncaught JavaScript errors in the application (Puppeteer `pageerror` events) are captured and can fail the test that triggered them, instead of letting the suite run on and produce confusing cascading timeouts.

- `src/lib/pageErrors.js` — module-level store for page errors. Records each error, exposes a one-shot abort signal (`onAbort()`) and a formatted listing (`format()`).
- `proxyConsole(page)` attaches the `pageerror` listener (idempotent per page) and records every error.
- `setupTests.js` measures the page-error count before/after each test and takes screenshots of all open pages on failure.

Behavior is controlled by two env vars (see Configuration below):
- `TEST_THROW_ON_PAGE_ERROR=true` — a test fails if a page error occurs during it; in-progress waits are aborted early via `raceWithPageError()` / `assertNotAborted()`.
- `TEST_PAGE_ERROR_STACKTRACE=true` — prints the (Puppeteer-reconstructed) in-page stack trace for each error.

When enabled, `afterAll` prints a `PAGE ERROR SUMMARY` block for the file.

## Descriptive Timeout Errors

All wait/click helpers wrap their underlying `waitForSelector` / `waitForFunction` and, on timeout, throw a descriptive error naming the helper, the selector/label, and the waited duration. The original Puppeteer error is attached as `error.cause` (Jest prints it under the main error). Example:

    Error: clickOnElementWithLabel: timed out after 5000ms looking for
           ".t-app-timetracker div.x-grid3-col-description" with exact text "test description 12345"
        Cause: TimeoutError: Waiting failed: 5000ms exceeded

## Helpers

| Helper | Purpose |
|--------|---------|
| `clickOnElement(page, selector, options)` | Wait for a visible element and click it. |
| `clickOnElementWithLabel(page, selector, label, options)` | Wait for an element whose exact (whitespace-normalized) text equals `label` and click it; retries if the element is re-rendered between lookup and click. |
| `clickOnElementWaitPageClosed(page, selector, label, options)` | Like `clickOnElementWithLabel`, then waits until the page/popup closes. |
| `waitForElement(page, selector, options)` | Wait for an element to `exist`, be `visible`, or be `hidden`. |
| `waitForActionableButton(page, text, timeout, labelSelector)` | Wait until a button with the given text is enabled and laid out. |
| `waitForPageHeader(page, timeout)` | Wait until the main app (dock + bar) is present. |
| `waitForAppReady(page, timeout)` | Wait until all loading masks are gone and the network is idle. |
| `refreshGridAndWaitForReady(page, appSelector, timeout)` | Click the grid refresh button and wait until the grid is ready again. (`formRefreshGrid` is an alias.) |
| `ensureFilterToolbarVisible(page, appSelector, {visible, timeout})` | Ensure the filter toolbar is shown/hidden. |
| `isElementUnblocked(page, selector)` | True if the element is on top at its center (not covered by a modal/mask). |
| `closeModalDialogs(page, timeoutOverall, timeoutDialogClosed)` | Close any open modal dialogs (used to unblock the app menu). |
| `openAppMenu(page, attempts)` | Robustly open the application menu, retrying while a modal blocks it. |
| `closeBrowser()` | Close all pages and the browser (use in `afterAll`). |

## Robustness in `getBrowser` / `getEditDialog` / `getNewWindow`

- `getBrowser` navigates with `waitUntil: 'domcontentloaded'`, waits for the page header, closes blocking modal dialogs, and opens the app menu with retries.
- `getEditDialog(text, page, {selector, clickCount, timeout})` accepts a specific selector (recommended, to avoid matching other apps) and uses `clickOnElementWithLabel`.
- `getNewWindow(timeout)` attaches the console/page-error proxy as early as possible and guards against unhandled rejections.

## Configuration (new env vars)

| Variable | Description |
|----------|-------------|
| `TEST_THROW_ON_PAGE_ERROR` | When `true`, a test fails if an uncaught page error occurs during it, and waits abort early. |
| `TEST_PAGE_ERROR_STACKTRACE` | When `true`, print the in-page stack trace for each recorded page error. |
| `TEST_TIMEOUT_MODAL_DIALOG` | Timeout (ms) for a modal dialog to appear/disappear (default `2000`). |

## Usage examples

    // Click an element by exact label and wait for the popup to close.
    await lib.clickOnElementWaitPageClosed(popupWindow, 'button', 'Ok');

    // Wait for a grid cell with an exact description, then delete it.
    await lib.clickOnElementWithLabel(page, '.t-app-timetracker div.x-grid3-col-description', testDescription);
    await page.keyboard.press('Delete');
    await lib.clickOnElement(page, '.modal.vue-message-box button.yes-button');
    await lib.waitForElement(page, '.modal.vue-message-box', {hidden: true});

    // Open a dialog with a specific button selector.
    const dialog = await lib.getEditDialog('Kontakt hinzufügen', page, {selector: '.t-app-addressbook .x-btn-text'});

    // Teardown.
    afterAll(async () => { await lib.closeBrowser(); });