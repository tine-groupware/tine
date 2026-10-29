import fs from 'fs';
import path from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default class MonitorResultReporter {
    onRunComplete(contexts, results) {
        const ok = results.numTotalTests > 0
            && results.numFailedTests === 0
            && results.numFailedTestSuites === 0;
        const payload = {
            test: 'EBHH Monitoring',
            status: ok ? 'OK' : 'NOT OK',
            timestamp: new Date().toISOString().replace(/\.\d{3}Z$/, 'Z'),
        };
        const outPath = path.resolve(__dirname, 'src', 'monitor', 'result.json');
        fs.writeFileSync(outPath, JSON.stringify(payload, null, 2) + '\n');
    }
}