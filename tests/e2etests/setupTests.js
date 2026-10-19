let currentTestName = '';
let currentTestFailed = false;

const trackTest = (testFn) => {
    const wrapped = (name, fn, timeout) => {
        if (typeof fn !== 'function') {
            return testFn(name, fn, timeout);
        }
        return testFn(name, async (...args) => {
            currentTestName = name;
            try {
                await fn(...args);
            } catch (error) {
                currentTestFailed = true;
                throw error;
            }
        }, timeout);
    };
    for (const property of Object.getOwnPropertyNames(testFn)) {
        if (property !== 'length' && property !== 'name') {
            wrapped[property] = testFn[property];
        }
    }
    return wrapped;
};

global.test = trackTest(global.test);
global.it = trackTest(global.it);

global.beforeEach(() => {
    currentTestFailed = false;
});

global.afterEach(async () => {
    if (!currentTestFailed) {
        return;
    }
    const pages = await browser.pages();
    for (let i = 1; i < pages.length; i++) {
        await pages[i].screenshot({path: 'screenshots/Error/' + currentTestName + i.toString() + '.png'});
    }
});
