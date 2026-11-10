import { SyncedCache } from "data/SyncedCache";

/**
 * fake source of change notifications
 */
const createLiveUpdates = (live = true) => {
    const changeListeners = [];
    const missedListeners = [];
    return {
        live,
        subscribedModels: null,
        subscribe(models, fn) {
            this.subscribedModels = models;
            changeListeners.push(fn);
            return () => changeListeners.splice(changeListeners.indexOf(fn), 1);
        },
        onMissedUpdates(fn) {
            missedListeners.push(fn);
            return () => missedListeners.splice(missedListeners.indexOf(fn), 1);
        },
        isLive() {
            return this.live;
        },
        change() {
            changeListeners.slice().forEach((fn) => fn({ topic: 'Tinebase.Tag.bulk' }));
        },
        missed() {
            missedListeners.slice().forEach((fn) => fn('connected'));
        },
        get listenerCount() {
            return changeListeners.length + missedListeners.length;
        },
    };
};

const createCache = (config = {}) => {
    let version = 0;
    const liveUpdates = config.liveUpdates || createLiveUpdates();
    const loader = jest.fn(async () => ({ version: ++version }));
    const cache = new SyncedCache(Object.assign({ name: 'test', models: ['Tinebase_Model_Tag'], loader, liveUpdates }, config));
    return { cache, loader, liveUpdates };
};

// let pending promises resolve
const flush = async () => {
    for (let i = 0; i < 5; i++) await Promise.resolve();
};

describe('data/SyncedCache', () => {
    beforeEach(() => {
        jest.useFakeTimers();
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    it('subscribes to changes of the given models', () => {
        const { liveUpdates } = createCache();
        expect(liveUpdates.subscribedModels).toEqual(['Tinebase_Model_Tag']);
    });

    it('loads lazy and once', async () => {
        const { cache, loader } = createCache();
        expect(cache.getData()).toBeUndefined();
        expect(loader).not.toHaveBeenCalled();

        const [a, b] = await Promise.all([cache.load(), cache.load()]);
        expect(a).toEqual({ version: 1 });
        expect(b).toBe(a);
        expect(await cache.load()).toBe(a);
        expect(cache.getData()).toBe(a);
        expect(loader).toHaveBeenCalledTimes(1);
    });

    it('does not load on changes before it is used', async () => {
        const { cache, loader, liveUpdates } = createCache();
        liveUpdates.change();
        liveUpdates.missed();
        jest.runAllTimers();
        await flush();
        expect(loader).not.toHaveBeenCalled();
        expect(cache.isLoaded()).toBe(false);
    });

    it('reloads once on (multiple) changes and notifies listeners', async () => {
        const { cache, loader, liveUpdates } = createCache();
        await cache.load();
        const listener = jest.fn();
        cache.onChange(listener);

        liveUpdates.change();
        liveUpdates.change();
        // outdated data is returned until reload is done
        expect(cache.getData()).toEqual({ version: 1 });
        expect(cache.isFresh()).toBe(false);

        jest.advanceTimersByTime(cache.reloadDelay);
        await flush();
        expect(loader).toHaveBeenCalledTimes(2);
        expect(cache.getData()).toEqual({ version: 2 });
        expect(cache.isFresh()).toBe(true);
        expect(listener).toHaveBeenCalledWith({ version: 2 });
    });

    it('reloads, when updates might have been missed', async () => {
        const { cache, loader, liveUpdates } = createCache();
        await cache.load();
        liveUpdates.missed();
        jest.advanceTimersByTime(cache.reloadDelay);
        await flush();
        expect(loader).toHaveBeenCalledTimes(2);
    });

    it('reloads again, if changes come in while loading', async () => {
        let resolveLoad;
        const liveUpdates = createLiveUpdates();
        let version = 0;
        const loader = jest.fn(() => new Promise((resolve) => { resolveLoad = () => resolve({ version: ++version }); }));
        const cache = new SyncedCache({ models: [], loader, liveUpdates });

        const loading = cache.load();
        await flush();
        liveUpdates.change();
        resolveLoad();
        await loading;
        expect(cache.isFresh()).toBe(false);

        jest.advanceTimersByTime(cache.reloadDelay);
        await flush();
        resolveLoad();
        await flush();
        expect(loader).toHaveBeenCalledTimes(2);
        expect(cache.getData()).toEqual({ version: 2 });
    });

    it('does not notify listeners, if data did not change', async () => {
        const liveUpdates = createLiveUpdates();
        const cache = new SyncedCache({ models: [], loader: async () => ['same'], liveUpdates });
        await cache.load();
        const listener = jest.fn();
        cache.onChange(listener);

        await cache.load({ force: true });
        expect(listener).not.toHaveBeenCalled();
    });

    it('uses fallbackTTL without live updates', async () => {
        const { cache, loader } = createCache({ liveUpdates: createLiveUpdates(false), fallbackTTL: 1000 });
        await cache.load();
        expect(cache.isFresh()).toBe(true);

        jest.advanceTimersByTime(1001);
        expect(cache.isFresh()).toBe(false);
        // outdated data is returned and reloaded in background
        expect(cache.getData()).toEqual({ version: 1 });
        jest.advanceTimersByTime(0);
        await flush();
        expect(loader).toHaveBeenCalledTimes(2);
        expect(cache.getData()).toEqual({ version: 2 });
    });

    it('keeps data fresh with live updates', async () => {
        const { cache, loader } = createCache({ fallbackTTL: 1000 });
        await cache.load();
        jest.advanceTimersByTime(60000);
        expect(cache.isFresh()).toBe(true);
        await cache.load();
        expect(loader).toHaveBeenCalledTimes(1);
    });

    it('retries after load errors', async () => {
        const liveUpdates = createLiveUpdates();
        const loader = jest.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue('data');
        const cache = new SyncedCache({ models: [], loader, liveUpdates });

        await expect(cache.load()).rejects.toThrow('offline');
        expect(cache.isLoaded()).toBe(false);
        expect(await cache.load()).toBe('data');
    });

    it('removes failing listeners (e.g. of closed popups)', async () => {
        const { cache, liveUpdates } = createCache();
        jest.spyOn(console, 'error').mockImplementation(() => {});
        await cache.load();
        const failing = jest.fn(() => { throw new Error('freed script'); });
        const listener = jest.fn();
        cache.onChange(failing);
        cache.onChange(listener);

        await cache.load({ force: true });
        await cache.load({ force: true });
        expect(failing).toHaveBeenCalledTimes(1);
        expect(listener).toHaveBeenCalledTimes(2);
        expect(liveUpdates.listenerCount).toBe(2);
        console.error.mockRestore();
    });

    it('unsubscribes on destroy', async () => {
        const { cache, loader, liveUpdates } = createCache();
        await cache.load();
        const off = cache.onChange(jest.fn());
        off();
        expect(cache.listeners).toEqual([]);

        cache.destroy();
        expect(liveUpdates.listenerCount).toBe(0);
        cache.invalidate();
        jest.runAllTimers();
        await flush();
        expect(loader).toHaveBeenCalledTimes(1);
    });
});

describe('data/SyncedCache registry', () => {
    it('creates defined caches once', () => {
        const liveUpdates = createLiveUpdates();
        const configFn = jest.fn(() => ({ models: [], loader: async () => [], liveUpdates }));
        SyncedCache.define('test.registry', configFn);

        const cache = SyncedCache.get('test.registry');
        expect(cache).toBeInstanceOf(SyncedCache);
        expect(cache.name).toBe('test.registry');
        expect(SyncedCache.get('test.registry')).toBe(cache);
        expect(configFn).toHaveBeenCalledTimes(1);
    });

    it('throws for undefined caches', () => {
        expect(() => SyncedCache.get('test.undefined')).toThrow('not defined');
    });
});
