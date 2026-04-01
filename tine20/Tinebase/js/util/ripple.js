/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

function execute(target, fn, includeSelf, ...args) {
    const results = [];

    if (includeSelf) {
        results.push(fn(target, ...args));
    }

    if (target && typeof target.each === 'function') {
        target.each(item => results.push(fn(item, ...args)));
    }

    return results.length === 1 ? results[0] : results;
}

function dispatch(targets, fn, includeSelf, ...args) {
    if (Array.isArray(targets)) {
        return ripple(targets.map(item => fn(item, ...args)), false);
    }

    return ripple(execute(targets, fn, includeSelf, ...args), false);
}

/**
 * Creates a chainable wrapper around one or more targets.
 * When a target exposes an `each` method, any applied function will
 * ripple through the target itself (if includeSelf is true) and all
 * items yielded by `each`.
 * After the first apply, the chain operates on the flat results without
 * re-expanding any `each` collections.
 *
 * @param {*|Array} targets - A single value, object with an `each` method, or array of either.
 * @param {boolean} [includeSelf=true] - Whether to also apply functions to the target itself,
 *                                       in addition to its `each` items.
 * @returns {RippleChain}
 *
 * @example
 * ripple(myCollection).apply(upper).apply(trim).value();
 * // → ["GROUP", "A", "B", "C"]  (trim runs on the already-flat results, not re-expanded)
 */
export default function ripple(targets, includeSelf = true) {
    return {
        /**
         * Applies a function to all targets, rippling through any `each` collections.
         * Returns a new ripple wrapper around the flat results — subsequent apply calls
         * will not re-expand `each` collections.
         *
         * @param {Function} fn - The function to apply. Receives the target as first argument.
         * @param {...*} args - Additional arguments passed to fn after the target.
         * @returns {RippleChain}
         *
         * @example
         * ripple(["  hello  ", "  world  "])
         *   .apply(trim)
         *   .apply(upper)
         *   .value();
         * // → ["HELLO", "WORLD"]
         */
        apply(fn, ...args) {
            return dispatch(targets, fn, includeSelf, ...args);
        },

        /**
         * Unwraps and returns the current value of the chain.
         *
         * @returns {*|Array} The processed target(s).
         *
         * @example
         * ripple("  hello  ").apply(trim).value();
         * // → "hello"
         */
        value() {
            return targets;
        }
    };
}