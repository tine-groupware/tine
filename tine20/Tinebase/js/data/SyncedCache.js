/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * client side cache of small server side data (e.g. tags, base data of an application),
 * which is kept up to date without reloading the client
 *
 * - data is loaded once (on boot with `preload`, otherwise on first use)
 * - changes of records of the watched models trigger a reload (broadcasthub and own changes, postal channel recordchange)
 * - data is reloaded, when updates might have been missed: broadcasthub (re)connected, client was offline or asleep
 * - without live updates (no broadcasthub) data is reloaded on use, when it is older than `fallbackTTL`
 * - caches live in the main window, popups use the caches of the main window
 *
 * usage:
 *   SyncedCache.define('MyApp.baseData', () => ({
 *       models: ['MyApp_Model_Role', 'MyApp_Model_Status'],
 *       loader: async () => (await Tine.MyApp.getBaseData()),
 *   }), { preload: true });
 *
 *   const cache = SyncedCache.get('MyApp.baseData');
 *   cache.getData();                   // current data (sync), undefined if not loaded yet
 *   await cache.load();                // loaded and up to date data
 *   const off = cache.onChange((data) => { ... }); // data got reloaded and changed
 */

const DEFAULTS = {
    // collect changes and reload once
    reloadDelay: 300,
    // max age of data, if no live updates are available
    fallbackTTL: 5 * 60 * 1000,
};

// timer gaps above are considered as sleep (missed updates)
const SLEEP_CHECK_INTERVAL = 10000;
const SLEEP_THRESHOLD = 60000;

export class SyncedCache {
    /**
     * @param {Object} config
     *  - name {String}
     *  - models {String[]} php class names of the models, changes of their records invalidate the cache
     *  - loader {Function} async function returning the data
     *  - reloadDelay {Number} ms
     *  - fallbackTTL {Number} ms
     *  - liveUpdates {Object} source of change notifications (see getLiveUpdates), defaults to postal + broadcasthub
     */
    constructor(config) {
        Object.assign(this, DEFAULTS, { models: [] }, config);
        this.liveUpdates = this.liveUpdates || getLiveUpdates();

        this.data = undefined;
        this.loadedAt = 0;
        this.invalid = false;
        this.loadPromise = null;
        this.reloadTimer = null;
        this.listeners = [];

        this.unsubscribes = [
            this.liveUpdates.subscribe(this.models, () => this.invalidate()),
            this.liveUpdates.onMissedUpdates(() => this.invalidate()),
        ];
    }

    isLoaded() {
        return this.loadedAt > 0;
    }

    /**
     * @return {Boolean} data is loaded and up to date
     */
    isFresh() {
        return this.isLoaded() && !this.invalid
            && (this.liveUpdates.isLive() || Date.now() - this.loadedAt < this.fallbackTTL);
    }

    /**
     * get current data (sync)
     * NOTE: outdated data is returned while it gets reloaded in background
     *
     * @return {*} data or undefined, if not loaded yet
     */
    getData() {
        if (this.isLoaded() && !this.isFresh()) {
            this.invalid = true;
            this.scheduleReload(0);
        }
        return this.data;
    }

    /**
     * load data, if not loaded or outdated
     *
     * @param {Object} options
     *  - force {Boolean} reload, even if data is up to date
     * @return {Promise} resolves with data
     */
    load({ force = false } = {}) {
        if (!force && this.isFresh()) {
            return Promise.resolve(this.data);
        }
        if (!this.loadPromise) {
            // NOTE: changes during load invalidate the cache again -> reload after load
            this.invalid = false;
            this.loadPromise = Promise.resolve().then(() => this.loader()).then((data) => {
                const changed = !this.isLoaded() || !isEqual(this.data, data);
                this.data = data;
                this.loadedAt = Date.now();
                this.loadPromise = null;
                if (this.invalid) {
                    this.scheduleReload();
                }
                if (changed) {
                    this.fireChange();
                }
                return data;
            }, (e) => {
                this.loadPromise = null;
                this.invalid = true;
                throw e;
            });
        }
        return this.loadPromise;
    }

    /**
     * data might have changed on the server
     * NOTE: caches, which are not loaded yet, stay lazy
     */
    invalidate() {
        this.invalid = true;
        if (this.isLoaded() || this.loadPromise) {
            this.scheduleReload();
        }
    }

    scheduleReload(delay = this.reloadDelay) {
        if (this.reloadTimer || this.isDestroyed) return;
        this.reloadTimer = setTimeout(() => {
            this.reloadTimer = null;
            // a running load reloads afterwards, as cache is invalid
            if (!this.loadPromise) {
                this.load({ force: true }).catch(() => {});
            }
        }, delay);
    }

    /**
     * @param {Function} fn called with data after data got (re)loaded and changed
     * @return {Function} unsubscribe
     */
    onChange(fn) {
        this.listeners.push(fn);
        return () => {
            this.listeners = this.listeners.filter((listener) => listener !== fn);
        };
    }

    fireChange() {
        this.listeners.slice().forEach((fn) => {
            try {
                fn(this.data);
            } catch (e) {
                // NOTE: listeners of closed popups can't be called any more
                console.error(`[SyncedCache] ${this.name}: error in change listener, listener is removed`, e);
                this.listeners = this.listeners.filter((listener) => listener !== fn);
            }
        });
    }

    destroy() {
        this.isDestroyed = true;
        clearTimeout(this.reloadTimer);
        this.unsubscribes.forEach((unsubscribe) => unsubscribe());
        this.listeners = [];
    }
}

const isEqual = (a, b) => {
    try {
        return JSON.stringify(a) === JSON.stringify(b);
    } catch (e) {
        return false;
    }
};

/**
 * default source of change notifications
 *
 * - record changes: postal channel recordchange (broadcasthub and own changes)
 * - missed updates: broadcasthub (re)connected, client is online again or woke up
 * - live: broadcasthub is connected
 */
let liveUpdates = null;
const getLiveUpdates = () => {
    if (liveUpdates) return liveUpdates;

    const missedListeners = [];
    const notifyMissed = (reason) => missedListeners.slice().forEach((fn) => fn(reason));

    window.postal?.subscribe({
        channel: 'broadcasthub',
        topic: 'connected',
        callback: () => notifyMissed('connected'),
    });
    window.addEventListener('online', () => notifyMissed('online'));
    let lastTick = Date.now();
    window.setInterval(() => {
        const now = Date.now();
        if (now - lastTick > SLEEP_THRESHOLD) {
            notifyMissed('wakeup');
        }
        lastTick = now;
    }, SLEEP_CHECK_INTERVAL);

    liveUpdates = {
        subscribe(models, fn) {
            const subscriptions = models.map((model) => window.postal.subscribe({
                channel: 'recordchange',
                topic: `${String(model).replace('_Model_', '.')}.*`,
                callback: (data, envelope) => fn(envelope),
            }));
            return () => subscriptions.forEach((subscription) => subscription.unsubscribe());
        },
        onMissedUpdates(fn) {
            missedListeners.push(fn);
            return () => missedListeners.splice(missedListeners.indexOf(fn) >>> 0, 1);
        },
        isLive() {
            return !!(window.Tine?.Tinebase?.configManager?.get('broadcasthub')?.active
                && window.Tine?.Tinebase?.broadcastClient?.isConnected?.());
        },
    };
    return liveUpdates;
};

/**
 * registry of caches
 * NOTE: caches are defined in all windows, but created in the main window only
 */
const definitions = {};
const instances = {};

const getWindow = () => (typeof window === 'undefined' ? undefined : window);

const getMainWindow = () => {
    const win = getWindow();
    if (win?.isMainWindow === false && win.Ext?.ux?.PopupWindowMgr) {
        return win.Ext.ux.PopupWindowMgr.getMainWindow();
    }
    return win;
};

/**
 * define a cache
 *
 * @param {String} name
 * @param {Function} configFn returns the config of the cache (see SyncedCache constructor)
 * @param {Object} options
 *  - preload {Boolean} load on boot
 */
SyncedCache.define = (name, configFn, { preload = false } = {}) => {
    definitions[name] = { configFn, preload };
};

/**
 * get the cache (of the main window)
 *
 * @param {String} name
 * @return {SyncedCache}
 */
SyncedCache.get = (name) => {
    const mainWindow = getMainWindow();
    if (mainWindow !== getWindow()) {
        return mainWindow.Tine.Tinebase.data.SyncedCache.get(name);
    }
    if (!instances[name]) {
        if (!definitions[name]) {
            throw new Error(`[SyncedCache] cache ${name} is not defined`);
        }
        instances[name] = new SyncedCache(Object.assign({ name }, definitions[name].configFn()));
    }
    return instances[name];
};

/**
 * load caches with preload (main window only, called on boot)
 */
SyncedCache.preload = () => {
    if (getMainWindow() !== getWindow()) return;
    Object.keys(definitions).filter((name) => definitions[name].preload).forEach((name) => {
        SyncedCache.get(name).load().catch((e) => console.error(`[SyncedCache] ${name}: preload failed`, e));
    });
};

export default SyncedCache;
