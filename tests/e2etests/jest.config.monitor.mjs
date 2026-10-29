import path from 'path';
import {fileURLToPath} from "url";
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
import dotenv from 'dotenv';
dotenv.config({path: path.resolve(__dirname, '.env')});
dotenv.config({path: path.resolve(__dirname, '.env.monitor'), override: true});

export default {
    testEnvironment: 'node',
    reporters: ['default', '<rootDir>/jest.reporter.monitor.mjs'],
    globals: {
        browser: '',
        page: '',
    },
    roots: [
        "<rootDir>",
        process.env.PWD + "../../../tine20/vendor/metaways"
    ],
    testMatch: [
        "**/" + process.env.TEST_DIR + "/monitor/**/*.test.js"
    ],
    testPathIgnorePatterns: [
        "node_modules"
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