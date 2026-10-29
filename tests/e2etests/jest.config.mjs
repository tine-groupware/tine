import dotenv from 'dotenv';
dotenv.config();

export default {
    testEnvironment: 'node',
    globals: {
        browser: '',
        page: '',
    },
    roots: [
        "<rootDir>",
        process.env.PWD + "../../../tine20/vendor/metaways"
    ],
    testMatch: [
        "**/" + process.env.TEST_DIR + "/**/*.test.js",
        "**/tine20/vendor/metaways/*/tests/e2etests/*.test.js"
    ],
    testPathIgnorePatterns: [
        "node_modules",
        process.env.TEST_DIR + "/monitor"
    ],
    verbose: true,
    maxWorkers: process.env.TEST_WORKER,
    testTimeout: 60000,
    setupFilesAfterEnv: ["expect-puppeteer", "<rootDir>/setupTests.js"],
    // Helps resolve imports without .js extensions
    moduleNameMapper: {
        '^(\\.{1,2}/.*)\\.js$': '$1',
    },
};