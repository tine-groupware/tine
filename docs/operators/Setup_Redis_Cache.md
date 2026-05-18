# Redis application cache

{{ branding.title }} uses Zend Cache for application data (configuration, ACL grants, metadata, and similar). For production deployments, **Redis** (or compatible servers such as [Valkey](https://valkey.io/)) is the recommended backend instead of the file-based default.

This page covers the **`caching`** configuration only. Redis is also used for other features (sessions, action queue, broadcasthub) with separate settings. Those can share one Redis instance as long as each feature uses a distinct key prefix.

## What the application cache stores

The cache speeds up repeated reads of data that would otherwise hit the database or filesystem on every request. Typical examples include:

- Merged configuration from the database and `config.inc.php`
- ACL and container permission lookups
- Application metadata and similar derived data

Cache entries expire after the configured **lifetime** (default 3600 seconds). Expired entries are removed by the daily scheduler task `Tinebase_CacheCleanup` (`cleanupCache` on `Tinebase_Controller`).

!!! note "Not the same as mail or credential cache"
    Felamimail maintains its own IMAP message cache in the database. The **credential cache** for shared mail accounts is configured separately via `credentialCacheSharedKey`. Neither is controlled by the `caching` block documented here.

## Prerequisites

| Requirement | Notes |
|-------------|-------|
| Redis or Valkey | Supported versions are listed in the [system requirements](System_Requirements.md) (Redis 7.0 / Valkey 9.0). |
| PHP `redis` extension | Required when `backend` is `Redis`. The setup health check verifies the extension and TCP connectivity. |
| Network access | The PHP host must reach `host:port` (default `6379`). |

Install the extension on Debian/Ubuntu, for example:

```bash
apt install redis-server php-redis
```

## Configuration

Caching is configured in `config.inc.php` or in a file under `conf.d/` (see [Configuration](Config.md)). Set `'active' => true` and `'backend' => 'Redis'`.

Two equivalent layouts are supported. The application resolves host, port, and prefix from either the top-level `caching` keys or a nested `redis` array.

### Recommended: nested `redis` block

Used by the Docker image template (`etc/tine20/config.inc.php.mpl`):

```php title="conf.d/caching.inc.php"
<?php

return [
    'caching' => [
        'active'   => true,
        'lifetime' => 3600,
        'backend'  => 'Redis',
        'redis'    => [
            'host'   => '127.0.0.1',
            'port'   => 6379,
            'prefix' => 'tine20_',   // optional; see below
        ],
    ],
];
```

### Alternative: flat keys under `caching`

```php title="config.inc.php excerpt"
'caching' => [
    'active'   => true,
    'lifetime' => 3600,
    'backend'  => 'Redis',
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'prefix'   => 'tine20site',
],
```

### Configuration keys

| Key | Default | Description |
|-----|---------|-------------|
| `active` | — | Must be `true` to enable caching. |
| `backend` | `File` | Set to `Redis` for Redis. `Memcached` and `File` are also supported. |
| `lifetime` | `7200` if unset at runtime | Entry TTL in seconds (setup default is `3600`). |
| `redis.host` / `host` | `localhost` | Redis hostname or IP. |
| `redis.port` / `port` | `6379` | Redis port. |
| `redis.prefix` / `prefix` | database `tableprefix`, else `tine20` | Site-specific prefix; `_CACHE_` is appended automatically (see below). |
| `path` | temp directory | Only used for `File` backend; see [installation paths](Installation_Guide.md#paths). |
| `logging` | `false` | Log cache hits/misses/writes when the logger priority allows trace output. |
| `shared` | `false` | When `true`, configuration cache is shared across requests in multi-process setups. |

### Key prefix

All cache keys are stored with a prefix built as:

```text
{prefix}_CACHE_
```

If you omit `prefix`, {{ branding.title }} uses `database.tableprefix` (for example `tine20_`) or `tine20` as a fallback.

Use a **unique prefix per instance** when several {{ branding.title }} installations share one Redis server.

## Docker and environment variables

Official images map environment variables into `config.inc.php` via `etc/tine20/config.inc.php.mpl`:

| Variable | Default | Description |
|----------|---------|-------------|
| `TINE20_CACHING_ACTIVE` | `true` | Enable caching. |
| `TINE20_CACHING_LIFETIME` | `3600` | Entry lifetime in seconds. |
| `TINE20_CACHING_BACKEND` | `File` | Set to `Redis` for Redis. |
| `TINE20_CACHING_PATH` | `/var/lib/tine20/caching` | File backend directory only. |
| `TINE20_CACHING_REDIS_HOST` | — | **Required** when backend is `Redis`. |
| `TINE20_CACHING_REDIS_PORT` | `6379` | Redis port. |
| `TINE20_CACHING_REDIS_PREFIX` | `tine` | Key prefix (without `_CACHE_` suffix). |

Example from the reference [docker-compose](docker/docker-compose.yml):

```yaml
environment:
  TINE20_CACHING_BACKEND: Redis
  TINE20_CACHING_REDIS_HOST: cache
```

See [Docker options](docker/DOCKER-OPTIONS.en.md) for the full list of image variables.

## Sharing one Redis server

Production stacks often run a single Redis/Valkey container for several features. Use **different prefixes** (or separate logical databases) so keys do not collide:

| Feature | Config section | Typical key pattern |
|---------|----------------|-------------------|
| Application cache | `caching` | `{prefix}_CACHE_*` |
| PHP sessions | `session` | `{prefix}SESSION_*` |
| Action queue | `actionqueue` | `{queueName}*` |
| Broadcasthub | `broadcasthub.redis` | Pub/sub channel (not Zend Cache keys) |

Example: Docker Compose sets `TINE20_DATABASE_TABLEPREFIX` as the session prefix and uses host `cache` for caching, sessions, and the action queue.

## Rate limiting

API rate limits (`rateLimits` in config) use Redis only when `caching.backend` is `Redis`. If you rely on rate limiting, keep the cache backend on Redis and ensure connectivity.

## Clear cache after config changes

After changing cache-related settings, clear caches so workers do not keep stale data:

```sh title="clearCache"
--8<-- "scripts/docker-compose/clearCache"
```

On a bare-metal install:

```bash
php /usr/share/tine20/setup.php --config=/etc/tine20 --clear_cache -v
```

`--clear_cache` flushes the Zend Cache backend. With Redis, only keys matching the cache prefix are removed; session keys and unrelated Redis keys are left intact.

## Verify the setup

1. Open **Admin → Server information** (or run setup checks) and confirm the caching check passes.
2. Optional: enable cache logging in config (`'logging' => true`) and set logger priority to trace; see [Logging](Logging.md#logging-of-cache-hitstestssaves).
3. From the Redis CLI, confirm keys appear after use, for example:

```bash
redis-cli -h cache KEYS '*_CACHE_*'
```

## Troubleshooting

| Symptom | What to check |
|---------|----------------|
| Setup reports Redis connection failed | `redis` PHP extension loaded; host/port reachable from PHP; firewall between app and Redis. |
| Cache appears disabled after deploy | `caching.active` is `true`; `TINE20_CACHING_ACTIVE` not set to `false`; no startup exception in logs (failed cache init falls back to disabled cache). |
| Stale configuration after change | Run `--clear_cache`; restart PHP-FPM if opcode cache holds old bootstrap state. |
| Wrong data between instances | Distinct `prefix` / `TINE20_CACHING_REDIS_PREFIX` per installation on shared Redis. |
| Rate limits not enforced | `caching.backend` must be `Redis`. |

## Related documentation

- [Configuration](Config.md) — `conf.d` overlays
- [Docker quickstart](docker/DOCKER-QUICKSTART.en.md) — full stack with Redis
- [Broadcasthub](Setup_broadcasthub.md) — WebSocket notifications via Redis pub/sub
- [Action queue howto](howto/tine20AdminQueue.md) — queue maintenance on Redis
- [Logging](Logging.md) — cache operation logging
