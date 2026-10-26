const errors = [];
let aborted = false;
let abortPromise = null;
let abortResolve = null;

function ensureAbortPromise() {
    if (!abortPromise) {
        abortPromise = new Promise((resolve) => {
            abortResolve = resolve;
        });
    }
    return abortPromise;
}

export default {
    /**
     * Records an uncaught page error. The first call sets the abort flag and
     * resolves the shared abort promise (used to fail in-progress waits fast).
     *
     * @param {string} message
     */
    add: function (message) {
        errors.push(String(message));
        if (!aborted) {
            aborted = true;
            if (abortResolve) {
                abortResolve();
            }
        }
    },

    /**
     * @returns {number} Number of recorded page errors.
     */
    count: function () {
        return errors.length;
    },

    /**
     * @returns {string[]} Copy of all recorded page errors.
     */
    all: function () {
        return errors.slice();
    },

    /**
     * @returns {boolean} True once the first page error has been recorded.
     */
    isAborted: function () {
        return aborted;
    },

    /**
     * @returns {string} A numbered, multi-line representation of all recorded errors.
     */
    format: function () {
        if (errors.length === 0) {
            return '(no page errors recorded)';
        }
        return errors.map((e, i) => `  ${i + 1}. ${e}`).join('\n');
    },

    /**
     * Resolves when the first page error is recorded (immediately if one already was).
     * Used to race in-progress waits against a page error so they fail fast.
     *
     * @returns {Promise<void>}
     */
    onAbort: function () {
        return aborted ? Promise.resolve() : ensureAbortPromise();
    },
};