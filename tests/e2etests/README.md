# End-to-End Tests

## Intro

E2E tests (also called full-stack or broad-stack tests) verify workflows from the end-user's perspective: they drive the real tine application in a real browser and reproduce the use cases a user would actually perform, covering full-stack scenarios across the whole stack (UI, server, database). They are complex, error-prone, and require significant maintenance — therefore they should only be run at the end of the test cycle, against a realistic test environment.

TODO: Explain why E2E tests have to run on the host machine.

## About Puppeteer

- Node.js library for browser automation
- Controls Chrome/Chromium via the DevTools Protocol (CDP)
- Manual browser actions can be imitated with Puppeteer:
  - Taking screenshots
  - Testing single-page applications
  - Server-side rendering
  - Forms, UI tests, entering input, ...
- Timeline tracing for performance optimization

## Dependencies & Installation

### Prerequisites

- Node.js >= 22.12 (LTS 22.x recommended) — minimum version required by Puppeteer 25
- npm (bundled with Node, v10+)
- Chrome/Chromium (installed automatically by Puppeteer)

Install or update Node.js via `n` (see [Ubuntu](#ubuntu) / [MacOS](#macos) sections).  
Or via [nvm](https://github.com/nvm-sh/nvm):

```bash
nvm install lts && nvm use lts
```

Verify with `node --version` (must be v22.12.0 or newer) and `npm --version`.

### Ubuntu

```bash
sudo apt install npm
npx @puppeteer/browsers install chrome@stable --install-deps
sudo npm i -g n
sudo n lts
```

Puppeteer system dependencies:

```bash
sudo apt install gconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget
```

See [Puppeteer troubleshooting](https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#chrome-headless-doesnt-launch-on-unix).

### MacOS

```bash
npm install
npx puppeteer browsers install
sudo npm i -g n
sudo n lts
```

### Install E2E dependencies

```bash
cd tests/e2etests
npm install
```

### Upgrade / Downgrade Puppeteer

```bash
cd tests/e2etests
rm -rf node_modules package-lock.json
rm -rf ~/.cache/puppeteer
PUPPETEER_SKIP_DOWNLOAD=true npm install
npx puppeteer browsers install chrome
npx puppeteer browsers install chrome-headless-shell
```

### List npm packages and versions

```bash
npm list --depth=0
```

## Configuration

E2E tests are configured via `tests/e2etests/.env` or environment variables:

| Variable | Description |
|----------|-------------|
| `TEST_URL` | Base URL of the tine instance |
| `TEST_USERNAME` | Login username |
| `TEST_PASSWORD` | Login password |
| `TEST_MODE` | `headless` is default behavior<br>`debug` lets you watch the browser do the magic |
| `TEST_TIMEOUT_*` | Override default timeouts (e.g., `TEST_TIMEOUT_FORM_VALUE_CHANGED`) |
| `TEST_NETWORK_CONDITIONS_*` | Simulates slow network speed and high latency |
| `TEST_CPU_THROTTLING_RATE_*` | Simulates slow CPU |

More variables and documentation in `tests/e2etests/.env`.

## Running Tests

Some tests require to have tine demodata installed:
```bash
cd $TINE_DOCKER_PATH
./console tine:demodata
```

When all requirements are met:
```bash
cd tests/e2etests
npm test                                    # all tests
npm test src/test/Calendar/calendar.test.js # single file
npm test src/test/Setup/setup.test.js       # setup tests
npm test src/test/Admin/                    # all files within a directory
```

Jest config: `tests/e2etests/jest.config.mjs`

## Tech Stack

| Package | Purpose |
|---------|---------|
| `jest` ^29.7.0 | Test runner |
| `puppeteer` ^25.11.0 | Browser automation (Chrome/Chromium via DevTools Protocol) |
| `jest-puppeteer` ^11.0.0 | Jest integration for Puppeteer |
| `expect-puppeteer` ^11.0.0 | Custom Jest matchers for Puppeteer |
| `dotenv` ^16.6.1 | Environment variables from `.env` |

## Directory Structure

```
tests/e2etests/
├── src/
│   ├── lib/              # Browser helpers, utilities
│   │   ├── browser.js           # Main entry
│   │   └── browser.helpers.js   # Waiting logic, throttling, login, form fillers, screenshots
│   ├── test/             # Functional tests by app module
│   │   └── {App}/{App}.test.js
│   └── testScreenshots/  # Screenshot-based tests
├── screenshots/          # Captured screenshots
├── .env                  # Configuration
├── jest.config.mjs       # Jest configuration
├── jest-puppeteer.config.cjs
└── setupTests.js         # Global test setup
```

Each app has its own test file, mirroring the unit test structure.

## Core Library

### `src/lib/browser.js`

Main entry point. Key exports:

| Function | Purpose |
|----------|---------|
| `getBrowser(app, module)` | Initialize browser, login, navigate to app/module |
| `getEditDialog(btnText, page)` | Click button, wait for popup, handle loading masks |
| `getNewWindow()` | Open new window and return page object |
| `getCurrentUser(page)` | Get logged-in user account info |

### `src/lib/browser.helpers.js`

Implements waiting logic, throttling, login, form fillers, screenshot utilities etc.

| Function | Purpose |
|----------|---------|
| `formFillInputField(page, options)` | Fill input/textarea with typing delay simulation |
| `formFillComboField(page, options)` | Fill combo box with typing delay and selection |
| `formRefreshGrid(page, appSelector)` | Refresh grid and wait until ready again |
| `getEnv('NAME')`<br>`getEnvInt('NAME')`<br>`getEnvBool('NAME')`<br>`getEnvJson('NAME')`  | Return parsed value from .env |
| `makeScreenshot(page, options)` | Take a screenshot, `TEST_ALL_SCREENSHOT=true` captures both light and dark mode |

All helper functions can be accessed via `browser.js`, so only one import is necessary. 

TODO: Explain differences between spinner input / regular input / combo input / textarea fields.

## Test Structure

TODO: Pull nicer examples from e2etests.md.

```javascript
import { expect as expectPuppeteer } from 'expect-puppeteer';
import lib from '../../lib/browser.js';

// .env variables via: lib.getEnvInt('ENV_VAR_NAME')

describe('App Mainpage', () => {
    describe('Subsection', () => {
        test('description', async () => {
            // ...
        });
    });
});

describe('EditDialog', () => {
    describe('Contact', () => {
        test('add Contact', async () => {
            // ...
        });
    });
});
```

### Lifecycle Hooks

```javascript
// Global setup: runs once before all tests
beforeAll(async () => {
    await lib.getBrowser('Admin');
});

// Per-test setup
beforeEach(async () => { ... });

// Per-test teardown (runs after each test or on error)
afterEach(async () => { ... });

// Global teardown: runs after the last test
afterAll(async () => {
    global.browser.close();
});
```

## Writing Tests

### Finding CSS Selectors

- Keep the browser dev console open
- Use the selector tool to find CSS classes
- Verify uniqueness — some buttons across modules share the same class
- If not unique, make the selector more specific
- Start with `waitForSelector()` and `waitForFunction()` with high timeouts, then reduce
- Debug output via `console.log()` / `.warn()` / `.error()` — appears in terminal output
  - **Note:** Does not work inside `waitForFunction()` / `page.evaluate()` — use `throw new Error()` or return the result and log externally
- Be creative with combo fields — expandable elements may appear multiple times on a page
- Watch element visibility — a selector may find an element that is not yet visible or clickable

### Key Operations

**Clicking elements:**

```javascript
await page.click('.selector');                        // simple click
await expect(page).toClick('span', {text: 'Label'});  // matches selector, textContent must contain "Label"
```

**Typing into input / textarea elements:**

```javascript
await lib.formFillInputField(page, {selector: 'input', value: 'value', expectedValue: 'autocompleted value', typingDelay: 100});
await lib.formFillComboField(page, {selector: 'input', inputValue: 'test', itemValue: 'Test Timeaccount', typingDelay: 100});
```

**Reading element values:**

```javascript
let value = await page.evaluate(() => document.querySelector('input[name=container_id]').value);
```

**Waiting for elements:**

```javascript
await page.waitForSelector('.selector', {timeout: 20000});
await page.waitForFunction(() => document.querySelector('.element')?.textContent === 'updated value', {timeout: 20000});
await expect(page).toMatchElement('.x-combo-list-item', {text: 'Freiwillig'});
```

## Refactoring Legacy Tests

For detailed guidance on refactoring legacy E2E tests (eliminating `setTimeout`, replacing brittle assertions, standardizing form interactions), see [`tests/e2etests/AGENTS.md`](./AGENTS.md).

## Best Practices

- Think like an end user — test real-world workflows
- Cover happy paths / golden paths — typical user scenarios
- Focus on risk analysis — test important parts of the software
- Set up test environments efficiently — document them!
- E2E tests are the last line of defense — run them against realistic environments

## Documentation

- [Puppeteer API](https://pptr.dev/api)
- [expect-puppeteer](https://www.npmjs.com/package/expect-puppeteer)
- [Jest Configuration](https://jestjs.io/docs/en/configuration)
