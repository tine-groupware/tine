---
name: majorversionbump
description: >-
  Create and maintain update scripts for bumping all tine Groupware applications to a new major version.
license: AGPL-3.0
metadata:
  author: Philipp Schüle <p.schuele@metaways.de>
  version: "1.0"
---

# majorversionbump

## Purpose
Create and maintain update scripts for bumping all tine Groupware applications to a new major version.

## When to Use
- Bumping all apps from one major version to the next (e.g., 19.x -> 20.0, 20.x -> 21.0)
- Creating `Setup/Update/{N}.php` files for all apps
- Updating the first (top-level) `<version>` tag in all `setup.xml` files

## Prerequisites

Before starting, discover all apps:

```bash
# List all apps with setup.xml
find /tine20/tine20 -name "setup.xml" -path "*/Setup/setup.xml"
```

For each app, determine:
1. **APP_NAME constant** — check `{App}/Config.php` for `const APP_NAME = '...'`
2. **Current version** — grep `<version>` in `setup.xml`
3. **Priority** — Tinebase uses `PRIO_TINEBASE_UPDATE` (300), all others use `PRIO_NORMAL_APP_UPDATE` (1000)
4. **Edge cases** — Some apps have no Config.php (Setup) or no Config.php at all (Scheduler doesn't exist)

## Step 1: Create Update Scripts

For each app, create `{App}/Setup/Update/{N}.php` (where N is the target major version, e.g., `20.php`):

```php
<?php

/**
 * tine Groupware
 *
 * @package     {AppName}
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) {YEAR} Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is {YEAR}.11 (ONLY!)
 */
class {AppPrefix}_Setup_Update_{N} extends Setup_Update_Abstract
{
    protected const RELEASE0{N}0_UPDATE000 = __CLASS__ . '::update000';

    static protected $_allUpdates = [
        self::PRIO_{PRIORITY}        => [
            self::RELEASE0{N}0_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate({ConfigClass}::APP_NAME, '{N}.0', self::RELEASE0{N}0_UPDATE000);
    }
}
```

### Naming Convention
- **Class name**: `{AppPrefix}_Setup_Update_{N}` where `{AppPrefix}` matches the app directory name (e.g., `Calendar`, `Addressbook`)
- **Constant**: `RELEASE0{N}0_UPDATE000` (e.g., `RELEASE020_UPDATE000` for version 20)
- **Priority**: `PRIO_NORMAL_APP_UPDATE` for all apps except Tinebase (`PRIO_TINEBASE_UPDATE`)
- **Year**: Use the next year (e.g., if bumping from 20.x, use `2027.11`)

### Special Cases
- **Tinebase**: Use `PRIO_TINEBASE_UPDATE` instead of `PRIO_NORMAL_APP_UPDATE`
- **Apps without Config.php**: Skip entirely (e.g., Setup, Scheduler don't have Config.php and should not get update scripts)
- **Apps without update scripts**: Create a simple `update000` that just calls `addApplicationUpdate()` — no schema changes unless explicitly needed

### Skip These Apps
- `Setup` — no Config.php, not a real app
- `Scheduler` — no Config.php, app doesn't exist in this codebase
- Any app whose Config.php does not define `APP_NAME`

## Step 2: Update setup.xml

Update the `<version>` tag in every app's `setup.xml` to the new major version:

```bash
for f in $(find /tine20/tine20 -name "setup.xml" -path "*/Setup/setup.xml"); do
  sed -i 's|<version>[^<]*</version>|<version>{N}.0</version>|' "$f"
done
```

## Step 3: Verify

```bash
# Count update files (should match app count)
find /tine20/tine20 -name "{N}.php" -path "*/Setup/Update/{N}.php" | wc -l

# Verify all setup.xml versions
for f in $(find /tine20/tine20 -name "setup.xml" -path "*/Setup/setup.xml"); do
  echo "$(grep '<version>' "$f") | $f"
done
```

## Notes
- The `{N}` in filenames is zero-padded to 2 digits (e.g., `20.php`, `21.php`)
- The constant name uses the same zero-padding (e.g., `RELEASE020_UPDATE000`)
- Version in `addApplicationUpdate()` uses the actual version string (e.g., `'20.0'`)
- Year in the header comment should be the next calendar year from the previous update scripts
- Always skip apps that don't have a Config.php with APP_NAME constant
