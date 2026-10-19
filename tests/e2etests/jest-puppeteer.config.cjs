module.exports = {
    launch: {
        headless: process.env.TEST_MODE !== 'debug',
    },
    browserContext: "incognito",
}