# E2E Test Agent Guidance

## Purpose

Guide agents working on E2E tests in `tests/e2etests/`. Covers project structure, core library, patterns, and refactoring legacy tests.

You can find extra details in `.agents/skills/e2etests/SKILL.md`.

## Project Overview

E2E tests verify workflows from the end-user's perspective using Jest + Puppeteer. They run against a live tine instance configured via `.env`.

### Tech Stack

| Package | Purpose |
|---------|---------|
| `jest` ^29.7.0 | Test runner |
| `puppeteer` ^25.11.0 | Browser automation |
| `jest-puppeteer` ^11.0.0 | Jest-Puppeteer integration |
| `expect-puppeteer` ^11.0.0 | Custom matchers |
| `dotenv` ^16.6.1 | Environment variables |

### Directory Structure

```
tests/e2etests/
├── src/
│   ├── lib/
│   │   ├── browser.js           # Main entry: getBrowser, getEditDialog, formFill*, etc.
│   │   └── browser.helpers.js   # Waiting logic, throttling, login, form fillers, screenshots
│   ├── test/            # Functional tests by app module
│   └── testScreenshots/ # Screenshot-based tests
├── .env                 # Configuration (TEST_URL, TEST_USERNAME, TEST_PASSWORD)
├── jest.config.mjs      # Jest configuration
└── setupTests.js        # Global test setup
```

### Running Tests

```bash
cd tests/e2etests
npm test                          # all tests
npm test src/test/Addressbook/Addressbook.test.js  # single file
```

## Core Library

### `src/lib/browser.js`

Main entry point. Key exports:

| Function | Purpose |
|----------|---------|
| `getBrowser(app, module)` | Initialize browser, login, navigate to app/module |
| `getEditDialog(btnText, page)` | Click button, wait for popup, handle loading masks |
| `formFillInputField(page, selector, value)` | Fill input with typing delay simulation |
| `formFillComboField(page, selector, value)` | Fill combo box with typing delay and selection |
| `waitForAppReady(page)` | Wait for loading indicators to disappear |
| `getCurrentUser(page)` | Get logged-in user account info |

### `src/lib/browser.helpers.js`

Implements waiting logic, throttling, login, form fillers, and screenshot utilities.

## Patterns

### Waiting

- **Dynamic waiting preferred:** `waitForSelector`, `waitForFunction` with configurable timeouts from env vars (`TEST_TIMEOUT_*`)
- **No hardcoded delays:** Eliminate `setTimeout` — use dynamic waiting instead
- **Timeouts:** Start high, then reduce. Use `TEST_TIMEOUT_WAIT_FOR_SELECTOR` for overrides

### Form Filling

- Use `formFillInputField(page, selector, value)` for text inputs (simulates typing delay)
- Use `formFillComboField(page, selector, value)` for combo boxes
- **Never** use `page.type()` or `page.fill()` directly

### Assertions

- **Replace `toMatchElement`:** Use `waitForSelector` + `page.evaluate` for text extraction
- **Normalize whitespace** before comparison (trim, collapse multiple spaces)

### Dialogs

- Use `getEditDialog(btnText)` for popup windows — handles loading masks and content readiness

### Navigation

- Use `getBrowser(app, module)` for app/module navigation
- Avoid manual menu clicks unless necessary

### Debugging

- `console.log()` / `.warn()` / `.error()` appears in terminal output
- **Does not work** inside `waitForFunction()` / `page.evaluate()` — use `throw new Error()` or return results and log externally

## Refactoring Legacy Tests

### Anti-Patterns

| Anti-Pattern | Replacement |
|--------------|-------------|
| `await new Promise(r => setTimeout(r, 500))` | `await page.waitForSelector('.selector', {visible: true})` |
| `await page.type(selector, text)` | `await browser.formFillInputField(page, selector, text)` |
| `await page.fill(selector, text)` | `await browser.formFillInputField(page, selector, text)` |
| `toMatchElement(selector, {text: 'exact'})` | `waitForSelector` + `evaluate` text extraction with trim/normalize |
| `waitForXPath('//div[contains(., "text")]')` | `waitForSelector` + helper-based text assertion |

### Refactoring Checklist

- [ ] Remove all `setTimeout` calls
- [ ] Replace `page.type`/`page.fill` with helper functions
- [ ] Replace `toMatchElement` with `waitForSelector` + text extraction
- [ ] Use `getEditDialog` for popup interactions
- [ ] Validate test runs successfully after changes
- [ ] Document any app-specific quirks or selectors

### Guidelines

1. **Waiting Logic:** Replace `setTimeout` with `waitForSelector` or `waitForFunction`. Use `TEST_TIMEOUT_*` env vars.
2. **Form Filling:** Use `formFillInputField` for text inputs, `formFillComboField` for combo boxes. Never use `page.type`/`page.fill` directly.
3. **Assertions:** Replace `toMatchElement` with `waitForSelector` + `page.evaluate` for text extraction. Normalize whitespace.
4. **Dialogs:** Use `getEditDialog(btnText)` for popups — handles loading masks and content readiness.
5. **Navigation:** Use `getBrowser(app, module)` for app/module navigation. Avoid manual menu clicks.
6. **Simplify:** Prefer minimal, readable waiting logic over over-engineered helpers.

### Reference Examples

- **Refactored:** `src/test/Timetracker/timesheet.test.js` — form filling, dynamic waiting, structured assertions
- **Refactored:** `src/testScreenshots/Timetracker/timetracker.test.js` — test structure, navigation, dialog handling
- **Legacy (before state):** `src/testScreenshots/Inventory/inventory.test.js` — heavy `setTimeout`, `page.type/fill`, brittle `toMatchElement`
