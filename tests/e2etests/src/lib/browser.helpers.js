const puppeteer = require('puppeteer');
const { expect: expectPuppeteer } = require('expect-puppeteer');
require('dotenv').config();
const simpleConsole = require('console');
const {blue, cyan, green, magenta, red, yellow} = require('colorette')
const lib = require("./browser");
const colors = {
    LOG: text => text,
    ERR: red,
    WAR: yellow,
    INF: cyan
};
const modes = ['light', 'dark'];
const priorities = {
    EME: 0,  // Emergency: system is unusable
    ALE: 1,  // Alert: action must be taken immediately
    CRI: 2,  // Critical: critical conditions
    ERR: 3,  // Error: error conditions
    WAR: 4,  // Warning: warning conditions
    NOT: 5,  // Notice: normal but significant condition
    INF: 6,  // Informational: informational messages
    DEB: 7,   // Debug: debug messages
    TRA: 8   // Debug: debug messages
};
const defaultTypingDelay = 50;

module.exports = {
    /**
     * Waits for a file to be downloaded in the specified directory and returns the filename.
     * It checks for the presence of a file that does not have the '.crdownload' extension, which indicates an ongoing download in Chrome.
     *
     * TODO: This method is not tested.
     * It is only called in browser.js -> download() and that method is not called right now.
     *
     * @param fs - The file system module to read the directory contents.
     * @param {string} downloadPath - The path to the directory where the file is being downloaded.
     * @param {number} timeout - Number of milliseconds to wait for the file being fully downloaded.
     * @returns {Promise<string>} A promise that resolves to the filename of the downloaded file.
     */
    waitForFileToDownload: async function (fs, downloadPath, timeout = this.getEnvInt('TEST_TIMEOUT_DOWNLOAD')) {
        const startTime = Date.now();
        const POLL_INTERVAL = 500;

        console.log('Waiting to download file...');

        while (true) {
            const files = fs.readdirSync(downloadPath);
            const filename = files[0];

            if (filename && !filename.endsWith('.crdownload')) {
                return filename;
            }

            if (Date.now() - startTime > timeout) {
                throw new Error(`Download timed out after ${timeout}ms in ${downloadPath}`);
            }

            // This setTimeout() call is actually necessary.
            await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL));
        }
    },

    /**
     * Wait until a button-like element with given visible text is actionable/clickable.
     *
     * @param {puppeteer.Page} page - Page or frame context
     * @param {string} text - Visible button text to match
     * @param {number} [timeout=7000] - Timeout in ms
     * @param {string} [labelSelector='.x-btn-text'] - Selector to find the text element (optional)
     * @returns {Promise<void>}
     */
    waitForActionableButton: async function (page, text, timeout = 7000, labelSelector = '.x-btn-text') {
        if (!page) throw new Error('waitForActionableButton: page is required');

        // Runs in page context: uses window.getComputedStyle and DOM checks
        await page.waitForFunction(
            (labelSelectorInner, textInner) => {
                const nodes = Array.from(document.querySelectorAll(labelSelectorInner || '.x-btn-text'));
                const el = nodes.find(e => e.textContent && e.textContent.trim() === textInner);
                if (!el) return false;
                const btn = el.closest('button') || el.parentElement;
                if (!btn) return false;
                const style = window.getComputedStyle(btn);
                // offsetParent !== null -> element is laid out; ensure not hidden or disabled
                return btn.offsetParent !== null &&
                    style.display !== 'none' &&
                    !btn.disabled &&
                    !btn.classList.contains('x-item-disabled');
            },
            {timeout},
            labelSelector,
            text
        );
    },

    /**
     * Wait until loading indicators have disappeared, no more network traffic is ongoing, and tine header is available.
     * @param {puppeteer.Page} page
     * @returns {Promise<void>}
     */
    waitForAppReady: async function (page) {
        await page.waitForSelector('.tine-viewport-waitcycle', {hidden: true, timeout: this.getEnvInt('TEST_TIMEOUT_MASK')});
        await page.waitForSelector('.ext-el-mask', {hidden: true, timeout: this.getEnvInt('TEST_TIMEOUT_MASK')});
        await page.waitForNetworkIdle({timeout: this.getEnvInt('TEST_TIMEOUT_NETWORK_TIMEOUT'), idleTime: this.getEnvInt('TEST_TIMEOUT_NETWORK_IDLE')});
        await page.waitForSelector('.tine-dock', {timeout: this.getEnvInt('TEST_TIMEOUT_CONTENT_READY')});
    },

    /**
     * Takes a screenshot of the given page with the specified options.
     * The env variable TEST_ALL_SCREENSHOT set to 'true' will take screenshots in both light and dark modes.
     *
     * @param {puppeteer.Page} page
     * @param options - The options for taking the screenshot, including the path where the screenshot should be saved.
     * @returns {Promise<void>} A promise that resolves when the screenshot(s) have been taken and saved.
     */
    makeScreenshot: async function (page, options) {
        if (this.getEnvBool('TEST_ALL_SCREENSHOT') && !options.onlySingleScreenshot) {
            const basePath = options.path;
            if (!basePath) {
                throw new Error('makeScreenshot: missing path for saving a screenshot');
            }

            for (const mode of modes) {
                const filePath = basePath.replace(
                    /(\.\w+)$/,
                    `_${mode}$1`
                );

                await page.evaluate((m) => {
                    document.body.className = document.body.className.replace(
                        /(light|dark)-mode/,
                        `${m}-mode`
                    );
                }, mode);

                const resolution = this.getEnvJson('TEST_RESOLUTION');
                await page.setViewport(resolution);
                await page.waitForFunction(
                    (m) => document.body.className.includes(`${m}-mode`),
                    { timeout: 2000 },
                    mode
                );

                await page.screenshot({ ...options, path: filePath });
            }
        } else {
            await page.screenshot(options);
        }
    },

    /**
     * Proxies console messages from the given page to the main console, filtering by log level and ignoring messages related to 'sockjs-node'.
     *
     * @param {puppeteer.Page} page
     * @returns {Promise<void>}
     */
    proxyConsole: async function (page) {
        const logLevel = this.getEnvInt('LOGLEVEL', {defaultValue: priorities['DEB']});
        page
            .on('console', message => {
                const type = message.type().substr(0, 3).toUpperCase()
                const messageText = message.text();
                if (logLevel >= priorities[type] && !messageText.match('sockjs-node')) {
                    const color = colors[type] || blue
                    simpleConsole.log(color(`${type} ${messageText}`))
                }
            })
            .on('pageerror', ({message}) => {
                if (logLevel >= priorities['ERR'] && !message.match('sockjs-node')) {
                    simpleConsole.log(red(message))
                }
            })
            .on('response', response => {
                if (logLevel >= priorities['DEB']) {
                    simpleConsole.log(green(`${response.status()} ${response.url()}`))
                }
            })
            .on('requestfailed', request => {
                const url = request.url();
                if (logLevel >= priorities['ERR'] && !url.match('sockjs-node')) {
                    simpleConsole.log(magenta(`${request.failure().errorText} ${url}`))
                }
            })
    },

    /**
     * Initializes Jasmine and expect-puppeteer with default options.
     * Sets a default timeout of 5000ms for all expect-puppeteer actions.
     *
     * @param {function} setDefaultOptions - The function to set default options for expect-puppeteer.
     * @returns void
     */
    initJasmineAndExpect: function (setDefaultOptions) {
        jasmine.getEnv().addReporter({
            specStarted: result => jasmine.currentTest = result
        });
        setDefaultOptions({timeout: 5000});
    },

    /**
     * Launches the Puppeteer browser with specified options and returns the browser instance.
     *
     * @returns {Promise<puppeteer.Browser>}
     */
    launchBrowser: async function () {
        const args = [
            '--lang=de-DE,de',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-popup-blocking',
            '--ignore-certificate-errors',
            '--start-maximized'
        ];

        const opts = {
            headless: this.getEnvStr('TEST_MODE') !== 'debug',
            //ignoreDefaultArgs: ['--enable-automation'],
            //slowMo: 250,
            defaultViewport: this.getEnvJson('TEST_RESOLUTION'),
            args: args
        };

        if (process.platform === 'darwin') {
            opts.executablePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
        }

        return await puppeteer.launch(opts);
    },

    /**
     * Creates a new page in the browser and configures it with necessary settings.
     * Sets up console logging, HTTP headers, viewport size, and optional authentication.
     *
     * @param {puppeteer.Browser} browser - The Puppeteer browser instance to create the page in.
     * @param {Object} [auth] - Optional authentication credentials for HTTP authentication.
     * @param {string} auth.username - The username for HTTP authentication.
     * @param {string} auth.password - The password for HTTP authentication.
     * @returns {Promise<puppeteer.Page>} A promise that resolves to the configured page object.
     */
    createConfiguredPage: async function (browser, {auth} = {}) {
        if (!browser) {
            throw new Error('createConfiguredPage: browser is not initialized');
        }
        const page = await browser.newPage();
        await this.proxyConsole(page);

        await page.setExtraHTTPHeaders({
            'Accept-Language': 'de'
        });

        await page.setViewport(this.getEnvJson('TEST_RESOLUTION'));
        if (auth) {
            await page.authenticate(auth);
        }

        // Simulate slow network and CPU throttling.
        await this.applyThrottling(page, {mode: 'main', displayInfo: 'Main Page'});

        return page;
    },

    /**
     * Switches the browser language to German if not already set and if not running in headless mode.
     *
     * @param {puppeteer.Page} page - The page object to perform the language switch on.
     * @returns {Promise<void>} A promise that resolves when the language switch is complete.
     */
    switchToGermanIfNeeded: async function (page) {
        if (this.getEnvStr('TEST_MODE') === 'debug' && this.getEnvStr('TEST_BROWSER_LANGUAGE') !== 'de') {
            console.log('switching to German');
            const langSelector = '#langChooser input[type=text]';
            await page.waitForSelector(langSelector, {visible: true});
            await page.click(langSelector);
            await expectPuppeteer(page).toClick('.x-combo-list-item', {text: 'Deutsch [de]'});
            await page.waitForFunction(() => !document.querySelector('.x-combo-list'), {timeout: 5000}).catch(() => {});
            await page.waitForSelector(langSelector, {visible: true, timeout: 10000});
        }
    },

    /**
     * Performs the login action on the given page using the provided user credentials.
     *
     * @param {puppeteer.Page} page - The page object to perform the login on.
     * @param {Object} credentials - An object containing the username and password for login.
     * @param {string} credentials.user - The username for login.
     * @param {string} credentials.pass - The password for login.
     * @returns {Promise<void>} A promise that resolves when the login process is complete.
     */
    login: async function (page, { user, pass }) {
        await page.waitForSelector('input[name=username]', { timeout: this.getEnvInt('TEST_TIMEOUT_CONTENT_READY') });
        await page.focus('input[name=username]');
        await page.waitForFunction(() => {
            const el = document.querySelector('input[name=username]');
            return !!el && document.activeElement === el && !el.disabled && !el.readOnly;
        }, { timeout: 10000 });

        await expectPuppeteer(page).toFill('input[name=username]', user, { delay: defaultTypingDelay });
        await expectPuppeteer(page).toFill('input[name=password]', pass, { delay: defaultTypingDelay });
        await expectPuppeteer(page).toClick('button', { text: 'Anmelden' });
    },

    /**
     * Applies network and CPU throttling to the given page based on the provided environment variables.
     *
     * @param {puppeteer.Page} page - The page to apply throttling to.
     * @param {Object} opts
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {string} [opts.mode='main']
     * @param {string} [opts.displayInfo='']
     * @returns {Promise<void>} A promise that resolves when the throttling has been applied.
     */
    applyThrottling: async function (page, opts) {
        opts = { mode: 'main', displayInfo: '', ...opts };

        // Object representing the network conditions to apply (see TEST_NETWORK_CONDITIONS_*), or null to skip network throttling.
        const envNetwork = this.getEnvJson(`TEST_NETWORK_CONDITIONS_${opts.mode.toUpperCase()}`, {defaultValue: null, skipValidation: true});

        // Number representing the CPU throttling rate to apply (see TEST_CPU_THROTTLING_RATE_*), or 0 to skip CPU throttling.
        const envCpuThrottling = this.getEnvInt('TEST_CPU_THROTTLING_RATE_MAIN', {skipValidation: true});

        if ((!envNetwork) && !envCpuThrottling) return;

        const DEFAULT_THROUGHPUT = 10 * 1024 * 1024; // 10 MB/s
        const DEFAULT_LATENCY = 20;

        let client;
        try {
            client = await page.createCDPSession();
        } catch (err) {
            console.warn('applyThrottling: could not create CDP session — skipping throttling', err);
            return;
        }

        if (opts.displayInfo) { console.log(`applyThrottling info: ${opts.displayInfo}`); }

        if (envNetwork) {
            if (typeof envNetwork !== 'object' || !['offline', 'downloadThroughput', 'uploadThroughput', 'latency'].some(k => k in envNetwork)) {
                console.warn('applyThrottling: env network conditions is not an object or keys are incorrect - skipping network emulation', envNetwork);
                return;
            }
            // If offline, set values to 0; otherwise use parsed numbers.
            const offline = !!envNetwork.offline;
            const download = offline ? 0 : this.safeNumber(envNetwork.downloadThroughput, DEFAULT_THROUGHPUT);
            const upload = offline ? 0 : this.safeNumber(envNetwork.uploadThroughput, DEFAULT_THROUGHPUT);
            const latency = offline ? 0 : this.safeNumber(envNetwork.latency, DEFAULT_LATENCY);

            if (offline || (Number.isFinite(download) && Number.isFinite(upload))) {
                if (offline) {
                    console.log('applyThrottling: offline network conditions');
                } else {
                    console.log(`applyThrottling: network throttling: ${download} B/s (${Math.round(download / 1024)} kB/s) down, ${upload} B/s (${Math.round(upload / 1024)} kB/s) up, ${latency} ms latency`);
                }

                try {
                    await client.send('Network.enable');
                    await client.send('Network.emulateNetworkConditions', {
                        offline: offline,
                        downloadThroughput: download,
                        uploadThroughput: upload,
                        latency: latency
                    });
                } catch (err) {
                    console.warn('applyThrottling: error applying network emulation - continuing', err);
                }
            } else {
                console.warn('applyThrottling: network config missing numeric download/upload and not offline - skipping network emulation', envNetwork);
            }
        }

        if (envCpuThrottling != null) {
            const rate = this.safeNumber(envCpuThrottling);
            if (rate >= 1) {
                try {
                    await client.send('Emulation.setCPUThrottlingRate', {rate: rate});
                    console.log(`applyThrottling: CPU throttling: ${rate}x slowdown`);
                } catch (err) {
                    console.warn('applyThrottling: error applying CPU throttling', err);
                }
            } else {
                console.warn('applyThrottling: env CPU throttling value invalid or <1 - skipping', envCpuThrottling);
            }
        }
    },

    /**
     * Retrieves an environment variable and parses it according to the specified type.
     * Supports 'int', 'bool', 'string', 'json' and 'auto' (default) types.
     * Undefined environment variables or parsing failures will return the default value.
     *
     * NOTE: The classic usage process.env[VAR_NAME] still works fine.
     *
     * @param {string} envName - The name of the environment variable to retrieve.
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {string} [opts.type='auto'] - The type to parse the environment variable as ('int', 'bool', 'string', 'json' or 'auto').
     * @param {*} [opts.defaultValue=null] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable.
     * @returns {*} The parsed environment variable value, or the default value if not set or invalid.
     */
    baseGetEnv: function (envName, opts = {}) {
        const {type = 'auto', defaultValue = null, skipValidation = false} = opts;

        if (!skipValidation) {
            if (typeof envName !== 'string' || envName.length === 0 || process.env[envName] === undefined) {
                console.warn(`baseGetEnv: variable "${envName}" is not valid, returning default value: ${defaultValue} (type=${typeof defaultValue})`);
                return defaultValue;
            }
        }

        const rawValue = process.env[envName];
        if (rawValue === undefined || rawValue === null) return defaultValue;

        const strValue = String(rawValue).trim();

        if (type === 'int') {
            return Number.isFinite(parseInt(strValue, 10)) ? parseInt(strValue, 10) : defaultValue;
        }
        if (type === 'bool') {
            if (['1', 'true', 'yes', 'on'].includes(strValue.toLowerCase())) return true;
            if (['0', 'false', 'no', 'off'].includes(strValue.toLowerCase())) return false;
            return defaultValue;
        }
        if (type === 'string') {
            return strValue;
        }
        if (type === 'json') {
            try {
                return JSON.parse(strValue);
            } catch {
                return defaultValue;
            }
        }
        // type = auto
        return strValue;
    },

    /**
     * Retrieves an environment variable and parses it as an integer.
     * Undefined environment variables or parsing failures will return the default value.
     *
     * @param {string} envName - The name of the environment variable to retrieve..
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {number} [opts.defaultValue=0] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable
     * @returns {number} The integer value of the environment variable, or the default value if not set or invalid.
     */
    getEnvInt: function (envName, opts = {}) {
        return this.baseGetEnv(envName, { type: 'int', defaultValue: 0, ...opts });
    },

    /**
     * Retrieves an environment variable and parses it as a string.
     * Undefined environment variables or parsing failures will return the default value.
     *
     * @param {string} envName - The name of the environment variable to retrieve.
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {string} [opts.defaultValue=''] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable.
     * @returns {string} The string value of the environment variable, or the default value if not set.
     */
    getEnvStr: function (envName, opts= {}) {
        return this.baseGetEnv(envName, { type: 'string', defaultValue: '', ...opts });
    },

    /**
     * Alias for {@link getEnvStr}.
     *
     * @param {string} envName - The name of the environment variable to retrieve.
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {string} [opts.defaultValue=''] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable.
     * @returns {string} The string value of the environment variable, or the default value if not set.
     */
    getEnv: function (envName, opts= {}) {
        return this.getEnvStr(envName, opts);
    },

    /**
     * Retrieves an environment variable and parses it as a boolean.
     * Undefined environment variables or parsing failures will return the default value.
     *
     * @param {string} envName - The name of the environment variable to retrieve.
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {boolean} [opts.defaultValue=false] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable.
     * @returns {boolean} The boolean value of the environment variable, or the default value if not set or invalid.
     */
    getEnvBool: function (envName, opts= {}) {
        return this.baseGetEnv(envName, { type: 'bool', defaultValue: false, ...opts });
    },

    /**
     * Retrieves an environment variable and parses it as JSON.
     *
     * @param {string} envName - The name of the environment variable to retrieve.
     * @param {Object} [opts] - Optional settings for parsing the environment variable.
     * @param {Object|null} [opts.defaultValue={}] - The default value to return if the environment variable is not set or cannot be parsed.
     * @param {boolean} [opts.skipValidation=false] - Skips the existence/type validation of the env variable.
     * @returns {Object} The parsed JSON object from the environment variable, or the default value if not set or invalid.
     */
    getEnvJson: function (envName, opts= {}) {
        return this.baseGetEnv(envName, { type: 'json', defaultValue: {}, ...opts });
    },

    /**
     * Safely converts a value to a number, and returns fallback value if conversion fails or value is not finite.
     * Supports converting hex to decimal, parsing strings with whitespaces and numbers with units (e.g. '10ms' -> 10).
     *
     * @param {*} v - The value to convert to a number.
     * @param {number} [fallback=0] - The value to return if the conversion fails or if the value is not finite.
     * @returns {number} The converted number, or the fallback value if conversion fails or if the value is not finite.
     */
    safeNumber: function (v, fallback = 0) {
        if (v == null) return fallback;

        if (typeof v === 'number') return Number.isFinite(v) ? v : fallback;

        const n = Number(v);
        if (Number.isFinite(n)) return n;

        if (typeof v === 'string') {
            const s = v.trim();
            if (s === '') return fallback;
            const f = parseFloat(s);
            return Number.isFinite(f) ? f : fallback;
        }
        return fallback;
    },

    /**
     * Inserts a value into an <input> or <textarea> field, either plain / autocomplete with dropdown items,
     * or a bigger combo autocomplete field with pagination and items to click on, and waits for the value to be updated.
     *
     * Combo mode is activated when `options.itemValue` is present — the method will type `options.inputValue`,
     * wait for the dropdown, click the matching item, and then wait for the expected value.
     *
     * Regular mode supports `TEST_BYPASS_INPUT_EXTJS_VALIDATION` to set the DOM value directly instead of typing.
     *
     * @param {puppeteer.Page} page - The page object to perform the actions on.
     * @param {Object} [options]
     * @param {string} [options.selector] - The selector of the input field.
     * @param {string} [options.value] - The value to insert (regular mode only).
     * @param {string} [options.inputValue] - The text to type into the field (combo mode only).
     * @param {string} [options.itemValue] - The combo box item to click (activates combo mode when present).
     * @param {string|null} [options.expectedValue=null] - Expected value after insertion. Defaults to `itemValue` (combo) or `value` (regular).
     * @param {number|null} [options.typingDelay=null] - Delay in ms between keystrokes (defaults to 50).
     * @returns {Promise<void>}
     */
    formFillField: async function (page, options) {
        const {
            selector,
            value,
            inputValue,
            itemValue,
            expectedValue,
            typingDelay
        } = options ?? {};

        const isCombo = itemValue != null;
        const allowedElements = isCombo ? ['input'] : ['input', 'textarea'];
        const inputText = isCombo ? inputValue : value;
        const expected = expectedValue ?? (isCombo ? itemValue : value);
        const delay = typingDelay ?? defaultTypingDelay;

        if (!selector) {
            throw new Error('formFillField: "selector" is required');
        }
        if (isCombo) {
            if (inputValue === undefined || inputValue === null) {
                throw new Error('formFillField: "inputValue" is required for combo mode');
            }
            if (itemValue === undefined || itemValue === null) {
                throw new Error('formFillField: "itemValue" is required for combo mode');
            }
        } else {
            if (value === undefined || value === null) {
                throw new Error('formFillField: "value" is required');
            }
        }

        await page.waitForSelector(selector, {visible: true});

        await page.evaluate((sel, allow) => {
            const el = document.querySelector(sel);
            if (!el) {
                throw new Error(`formFillField: Selector "${sel}" did not match any element.`);
            }
            // Validate that the selector points to one of the allowed elements.
            if (allow.indexOf(el.tagName.toLowerCase()) === -1) {
                throw new Error(`formFillField: Selector "${sel}" matched a <${el.tagName.toLowerCase()}> element, expected one of ${allow.join(', ')}.`);
            }
        }, selector, allowedElements);

        if (!isCombo && this.getEnvBool('TEST_BYPASS_INPUT_EXTJS_VALIDATION')) {
            // OPTION 1: Set the value directly in DOM to bypass ExtJS's intermediate mask/validation.
            // Note: This does not work with combo elements that let you click on an item from a list.

            await page.evaluate((sel, val) => {
                const el = document.querySelector(sel);
                el.focus();
                el.value = val;
                // Notify ExtJS that the DOM value changed.
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }, selector, inputText);

            await page.keyboard.press('Tab');
        } else {
            // OPTION 2: Mimic a user typing in the characters.

            // Focus, select all existing content reliably (cross-platform) and delete the selection.
            await page.evaluate((sel) => {
                const el = document.querySelector(sel);
                el.focus();
                el.select();
            }, selector);

            await page.keyboard.press('Backspace');

            // Wait for ExtJS to finish processing the deletion and the field is truly empty.
            await page.waitForFunction((sel) => {
                const el = document.querySelector(sel);
                return el && el.value.trim() === '';
            }, { timeout: this.getEnvInt('TEST_TIMEOUT_FORM_VALUE_CHANGED') }, selector);

            // At this point, ExtJS validation is actually not completed, and .toFill() gets interrupted by a race condition.
            // The above block is not working and this pause is needed for now.
            // TODO: Find a workaround to replace this 300ms pause.
            await new Promise(r => setTimeout(r, 300));

            // Type the input value with human-like delay.
            await page.type(selector, inputText, {delay: delay});

            if (!isCombo) {
                // Unfocus to trigger ExtJS validation.
                await page.keyboard.press('Tab');
            }
        }

        if (isCombo) {
            // Let the combo list open and click on the desired item.
            await expectPuppeteer(page).toMatchElement('.x-combo-list-item', {text: itemValue, visible: true});
            await expectPuppeteer(page).toClick('.x-combo-list-item', {text: itemValue});
        }

        // Wait for ExtJS to finish formatting/validation and match the expected value.
        try {
            await page.waitForFunction((sel, exp) => {
                const el = document.querySelector(sel);
                return !!el && el.value.trim() === exp;
            }, {timeout: this.getEnvInt('TEST_TIMEOUT_FORM_VALUE_CHANGED')}, selector, expected);
        } catch (error) {
            // Debug output to help identify the issue.
            const actualValue = await page.evaluate((sel) => {
                const el = document.querySelector(sel);
                return !!el ? el.value.trim() : null;
            }, selector);
            const debug = isCombo
                ? `formFillField: unexpected result: selector=${selector} | inputValue=${inputValue} | itemValue=${itemValue} | expected=${expected} | actual=${actualValue}`
                : `formFillField: unexpected result: selector=${selector} | value=${value} | expected=${expected} | actual=${actualValue}`;
            console.error(debug);
            throw error;
        }
    },

    /**
     * Inserts a value into an <input> or <textarea> field and waits for the value to be updated in it.
     *
     * @param {puppeteer.Page} page - The page object to perform the actions on.
     * @param {Object} [options]
     * @param {string} [options.selector] - The selector of the input field to insert the value into.
     * @param {string} [options.value] - The value to be inserted into the input field.
     * @param {string|null} [options.expectedValue=null] - The expected value in the input field after insertion. If null, it defaults to the inserted value.
     * @param {number|null} [options.typingDelay=null] - Increases pause between typing the letters into the field.
     * @returns {Promise<void>} A promise that resolves when the value has been inserted and updated in the input field.
     */
    formFillInputField: async function (page, options) {
        return this.formFillField(page, options);
    },

    /**
     * Inserts a value into a combo (autocomplete) input field specified by the selector, picks the desired item from the combo list,
     * and waits for the value to be updated in the input field.
     *
     * @param {puppeteer.Page} page - The page object to perform the actions on.
     * @param {Object} [options]
     * @param {string} [options.selector] - The selector of the input field to insert the value into.
     * @param {string} [options.inputValue] - The value to be inserted into the input field.
     * @param {string} [options.itemValue] - The combo box item to click on.
     * @param {string|null} [options.expectedValue=null] - The expected value in the input field after insertion. If null, it defaults to the inserted value.
     * @returns {Promise<void>} A promise that resolves when the value has been inserted and updated in the input field.
     */
    formFillComboField: async function (page, options) {
        return this.formFillField(page, options);
    },

    /**
     * Clicks on the grid refresh button and waits until the grid is fully loaded again.
     *
     * @param {puppeteer.Page} page - The page object to perform the actions on.
     * @param {string} appSelector - The selector of the current app, e.g. ".t-app-timetracker".
     * @returns {Promise<void>}
     */
    formRefreshGrid: async function (page, appSelector) {
        // Click on "refresh" button to update the grid.
        const buttonSelector = `${appSelector} .x-btn-image.x-tbar-loading`;
        await page.click(buttonSelector);

        // Wait until the refresh button is actionable again:
        // - The button doesn't contain the class "x-item-disabled".
        // - None of the ancestors until and including a table element contain the class "x-item-disabled".
        await page.waitForFunction(
            (sel) => {
                const el = document.querySelector(sel);
                if (!el) return false;
                const btn = el.closest('button') || el.parentElement;
                if (!btn) return false;
                const style = window.getComputedStyle(btn);

                // Check disabled state: traverse up to the nearest <table> ancestor
                let ancestor = btn;
                while (ancestor && ancestor.tagName !== 'TABLE') {
                    if (ancestor.classList.contains('x-item-disabled')) {
                        return false;
                    }
                    ancestor = ancestor.parentElement;
                }

                return btn.offsetParent !== null &&
                    style.display !== 'none' &&
                    !btn.disabled &&
                    ancestor !== null && ancestor.tagName === 'TABLE';
            },
            {timeout: this.getEnvInt('TEST_TIMEOUT_GRID_UPDATED')},
            buttonSelector
        );
    },
};