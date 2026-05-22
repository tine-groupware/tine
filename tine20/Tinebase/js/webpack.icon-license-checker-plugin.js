/**
 * IconLicenseCheckerPlugin
 *
 * Webpack plugin that intercepts unplugin-icons imports and validates the
 * license of the referenced Iconify icon set against a configurable allowlist.
 *
 * License data is read directly from the already-installed icon set packages,
 * so no additional dependency is required beyond what unplugin-icons itself needs.
 *
 * Resolution order (mirrors how unplugin-icons itself resolves icon data):
 *   1. @iconify-json/{prefix}/info.json          — per-set package (preferred)
 *   2. @iconify/json/json/{prefix}.json           — monorepo package (.info field)
 *
 * Import patterns matched:
 *   ~icons/{collection}/{icon}          (unplugin-icons Webpack/Rollup alias)
 *   virtual:icons/{collection}/{icon}   (Vite-style alias)
 *
 * Usage in webpack.config.js:
 *
 *   const IconLicenseCheckerPlugin = require('./icon-license-checker-plugin');
 *
 *   plugins: [
 *     new IconLicenseCheckerPlugin({
 *       // SPDX identifiers to allow (defaults shown below)
 *       allowedLicenses: ['MIT', 'Apache-2.0', 'ISC', 'BSD-2-Clause', 'BSD-3-Clause', 'CC0-1.0', 'Unlicense', 'OFL-1.1'],
 *       // 'error' (default) = build fails | 'warn' = only warning
 *       severity: 'error',
 *     }),
 *   ]
 */

'use strict';

const path = require('path');

// Matches ~icons/mdi/home  and  virtual:icons/mdi/home
const ICON_IMPORT_RE = /^(?:~icons|virtual:icons)\/([^/]+)\//;

const DEFAULT_ALLOWED = new Set([
    'MIT',
    'Apache-2.0',
    'ISC',
    'BSD-2-Clause',
    'BSD-3-Clause',
    'CC0-1.0',
    'Unlicense',
    'OFL-1.1',
]);

// ---------------------------------------------------------------------------
// License resolution — no extra package needed
// ---------------------------------------------------------------------------

/**
 * Try to read license info for a given prefix.
 *
 * Strategy 1: @iconify-json/{prefix}/info.json
 *   This is the small per-set package the user installed explicitly, e.g.:
 *     npm i -D @iconify-json/mdi
 *   The info.json has the shape:
 *     { "name": "...", "license": { "title": "...", "spdx": "..." }, ... }
 *
 * Strategy 2: @iconify/json/json/{prefix}.json
 *   The monorepo package (@iconify/json) stores everything in one big JSON per set.
 *   The top-level "info" field mirrors the IconifyInfo structure.
 *
 * @param {string} prefix  e.g. "mdi", "fa", "nrk"
 * @returns {{ spdxList: string[], source: string } | null}
 *   null when the icon set is not installed at all.
 */
function resolveLicense(prefix) {
    // --- Strategy 1: @iconify-json/{prefix}/info.json ---
    try {
        const infoPath = require.resolve(`@iconify-json/${prefix}/info.json`);
        const info = require(infoPath);
        return {
            spdxList: parseSpdx(info?.license?.spdx || info?.license?.title),
            source: `@iconify-json/${prefix}/info.json`,
        };
    } catch (_) {
        // Package not installed — fall through.
    }

    // --- Strategy 2: @iconify/json/json/{prefix}.json ---
    try {
        const monoRepoRoot = path.dirname(require.resolve('@iconify/json/package.json'));
        const setPath = path.join(monoRepoRoot, 'json', `${prefix}.json`);
        const data = require(setPath);
        return {
            spdxList: parseSpdx(data?.info?.license?.spdx || data?.info?.license?.title),
            source: `@iconify/json/json/${prefix}.json`,
        };
    } catch (_) {
        // Package not installed or prefix unknown — fall through.
    }

    return null;
}

/**
 * Split a raw SPDX string (possibly "MIT/Apache-2.0" or empty) into an array.
 * @param {string|undefined} raw
 * @returns {string[]}
 */
function parseSpdx(raw) {
    if (!raw) return [];
    return raw
        .split('/')
        .map((s) => s.trim())
        .filter(Boolean);
}

// ---------------------------------------------------------------------------
// Plugin
// ---------------------------------------------------------------------------

class IconLicenseCheckerPlugin {
    /**
     * @param {object}   [options]
     * @param {string[]} [options.allowedLicenses]  SPDX identifiers that are permitted
     * @param {'error'|'warn'} [options.severity]   'error' = build fails, 'warn' = warning only
     */
    constructor(options = {}) {
        this.allowedLicenses = new Set(options.allowedLicenses ?? [...DEFAULT_ALLOWED]);
        this.severity = options.severity ?? 'error';
        // Cache per prefix so we only hit the filesystem once per collection.
        this._cache = new Map();
    }

    /**
     * Check whether a collection prefix is allowed.
     * Result is cached after the first lookup.
     *
     * @param {string} prefix
     * @returns {{ allowed: boolean, message: string }}
     */
    _check(prefix) {
        if (this._cache.has(prefix)) return this._cache.get(prefix);

        let result;
        const resolved = resolveLicense(prefix);

        if (resolved === null) {
            result = {
                allowed: false,
                message:
                    `Icon set "@iconify-json/${prefix}" is not installed. ` +
                    `Install it with: npm i -D @iconify-json/${prefix}`,
            };
        } else if (resolved.spdxList.length === 0) {
            result = {
                allowed: false,
                message:
                    `Icon set "${prefix}" has no SPDX license information in ${resolved.source}. ` +
                    `Cannot verify compliance — treating as blocked.`,
            };
        } else {
            const blocked = resolved.spdxList.filter((s) => !this.allowedLicenses.has(s));
            if (blocked.length > 0) {
                result = {
                    allowed: false,
                    message:
                        `Icon set "${prefix}" uses license "${resolved.spdxList.join(' / ')}" ` +
                        `(source: ${resolved.source}). ` +
                        `Blocked identifiers: ${blocked.join(', ')}. ` +
                        `Allowed: ${[...this.allowedLicenses].join(', ')}.`,
                };
            } else {
                result = { allowed: true, message: '' };
            }
        }

        this._cache.set(prefix, result);
        return result;
    }

    /** @param {import('webpack').Compiler} compiler */
    apply(compiler) {
        const pluginName = 'IconLicenseCheckerPlugin';

        compiler.hooks.normalModuleFactory.tap(pluginName, (nmf) => {
            nmf.hooks.beforeResolve.tap(pluginName, (resolveData) => {
                const request = resolveData.request;
                const match = request.match(ICON_IMPORT_RE);
                if (!match) return;

                const prefix = match[1];
                const { allowed, message } = this._check(prefix);
                if (allowed) return;

                const full =
                    `[IconLicenseCheckerPlugin] License violation in "${request}":\n  ${message}`;

                if (this.severity === 'error') {
                    throw new Error(full);
                } else {
                    compiler.hooks.afterCompile.tap(pluginName, (compilation) => {
                        compilation.warnings.push(new Error(full));
                    });
                }
            });
        });
    }
}

module.exports = IconLicenseCheckerPlugin;