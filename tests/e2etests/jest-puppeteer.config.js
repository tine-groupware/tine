module.exports = {
    launch: {
        headless: process.env.TEST_MODE !== 'debug',
        executablePath: require('puppeteer').executablePath()
    },
    browserContext: "incognito",
}