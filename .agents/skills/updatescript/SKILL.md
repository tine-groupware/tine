---
name: updatescript
description: >-
  Create and maintain database update scripts for tine applications. Use when creating, modifying, or understanding
  Setup/Update PHP files that migrate database schema and application data between versions.
license: AGPL-3.0
metadata:
  author: Philipp Schüle with OpenCode and Qwen 3.6
  version: "1.0"
---

# Update Scripts

Update scripts handle database migrations and application state changes when tine is upgraded from one version to another.

## File Locations

Update scripts live in:

```
tine20/{AppName}/Setup/Update/{VersionNumber}.php
```

For example: `tine20/Addressbook/Setup/Update/19.php`

## Basic Structure

Every update script extends `Setup_Update_Abstract` and follows this pattern:

```php
<?php

/**
 * tine Groupware
 *
 * @package     {AppName}
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) {Year} Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      {Author Name} <{email}>
 *
 * this is {Version} (ONLY!)
 */
class {AppName}_Setup_Update_{Version} extends Setup_Update_Abstract
{
    protected const RELEASE{Version padded}_UPDATE{UpdateNumber padded} = __CLASS__ . '::update{UpdateNumber}';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE{Version padded}_UPDATE{UpdateNumber padded}    => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update{UpdateNumber}',
            ],
        ],
    ];

    public function update{UpdateNumber}(): void
    {
        // Migration logic here
        $this->addApplicationUpdate({AppName}_Config::APP_NAME, '{Version}.{Patch}', self::RELEASE{Version padded}_UPDATE{UpdateNumber padded});
    }
}
```

## Version Naming

- **File name**: `{MajorVersion}.php` (e.g., `19.php` for version 19.x)
- **Application version**: `{Major}.{Minor}` in `setup.xml` (e.g., `19.0`)
- **Update keys**: Track individual updates within a release (e.g., `19.0`, `19.1`, `19.2`)
- **Update numbering**: Zero-padded to 3 digits (`update000`, `update001`, etc.)
- **Constant naming**: `RELEASE{Version padded}_UPDATE{UpdateNumber padded}` (e.g., `RELEASE019_UPDATE001`)

## Update Priorities

Updates execute in priority order (lower numbers first):

| Constant | Value | When to Use |
|----------|-------|-------------|
| `PRIO_TINEBASE_BEFORE_EVERYTHING` | 1 | Tinebase setup before all other apps |
| `PRIO_TINEBASE_BEFORE_STRUCT` | 90 | Tinebase before schema changes |
| `PRIO_TINEBASE_STRUCTURE` | 100 | Tinebase schema operations |
| `PRIO_TINEBASE_AFTER_STRUCTURE` | 150 | Tinebase after schema changes |
| `PRIO_TINEBASE_UPDATE` | 300 | Tinebase general updates |
| `PRIO_NORMAL_APP_STRUCTURE` | 500 | Application schema changes |
| `PRIO_NORMAL_APP_UPDATE` | 1000 | Application data/logic updates |

## Common Operations

### 1. Simple Version Bump

For updates with no schema or data changes:

```php
public function update000(): void
{
    $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
}
```

### 2. Add a Database Column

```php
public function update001(): void
{
    if (!$this->_backend->columnExists('new_column', 'table_name')) {
        $this->_backend->addCol('table_name', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>new_column</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>'));
        
        if ($this->getTableVersion('table_name') < 3) {
            $this->setTableVersion('table_name', 3);
        }
    }
    $this->addApplicationUpdate(AppName_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
}
```

### 3. Update Doctrine Schema (Model-Driven)

When model definitions change (e.g., `NULLABLE => true` → `false`, field types, validators), use `Setup_SchemaTool::updateSchema()`.

**These updates must use `PRIO_NORMAL_APP_STRUCTURE` (500) or `PRIO_TINEBASE_STRUCTURE` (100) (for Tinebase updates)** so schema changes execute before data/logic updates:

```php
public function update002(): void
{
    Setup_SchemaTool::updateSchema([
        AppName_Model_Record::class,
        AppName_Model_RelatedRecord::class,
    ]);

    $this->addApplicationUpdate(AppName_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
}
```

Example with priority registration:

```php
static protected $_allUpdates = [
    self::PRIO_NORMAL_APP_STRUCTURE => [
        self::RELEASE019_UPDATE002 => [
            self::CLASS_CONST => self::class,
            self::FUNCTION_CONST => 'update002',
        ],
    ],
    self::PRIO_NORMAL_APP_UPDATE => [
        // data/logic updates go here
    ],
];
```

### 4. Data Migration

Migrate existing data to new format:

```php
public function update003(): void
{
    $records = AppName_Controller_Record::getInstance()->getAll();
    foreach ($records as $record) {
        // Transform data
        $record->some_field = $this->transformValue($record->some_field);
        AppName_Controller_Record::getInstance()->update($record);
    }

    $this->addApplicationUpdate(AppName_Config::APP_NAME, '19.3', self::RELEASE019_UPDATE003);
}
```

### 5. Multiple Updates in One File

A single version file can contain multiple update methods with different priorities:

```php
static protected $_allUpdates = [
    self::PRIO_NORMAL_APP_STRUCTURE     => [
        self::RELEASE019_UPDATE001          => [
            self::CLASS_CONST                   => self::class,
            self::FUNCTION_CONST                => 'update001',
        ],
    ],
    self::PRIO_NORMAL_APP_UPDATE        => [
        self::RELEASE019_UPDATE000          => [
            self::CLASS_CONST                   => self::class,
            self::FUNCTION_CONST                => 'update000',
        ],
        self::RELEASE019_UPDATE002          => [
            self::CLASS_CONST                   => self::class,
            self::FUNCTION_CONST                => 'update002',
        ],
    ],
];
```

## Available Methods from Setup_Update_Abstract

### Version Management

- `$this->getApplicationVersion($_application)` - Get current app version from database
- `$this->getTableVersion($_tableName)` - Get current table version number
- `$this->addApplicationUpdate($_appName, $_version, $_updateKey)` - Mark update as complete and bump app version
- `$this->hasApplicationUpdateRan($_appName, $_updateKey)` - Check if an update already ran (idempotency)
- `$this->setTableVersion($_tableName, $_version, $_createIfNotExist, $_application)` - Set table version manually
- `$this->increaseTableVersion($_tableName)` - Increment table version by 1

### Schema Operations

- `$this->createTable($_tableName, $_table, $_application, $_version)` - Create new table
- `$this->dropTable($_tableName, $_application)` - Drop table
- `$this->renameTable($_oldTableName, $_newTableName)` - Rename table
- `$this->_backend->addCol($_table, $_fieldDeclaration)` - Add column
- `$this->_backend->alterCol($_table, $_fieldDeclaration)` - Modify column
- `$this->_backend->dropCol($_table, $_columnName)` - Drop column
- `$this->_backend->columnExists($_column, $_table)` - Check if column exists
- `$this->_backend->tableExists($_table)` - Check if table exists

### Database Access

- `$this->_db` - Zend_Db_Adapter_Abstract instance for raw queries
- `$this->_backend` - Setup_Backend_Mysql instance for schema operations

### Helper Methods

- `$this->shortenTextValues($table, $field, $length)` - Truncate text fields exceeding length
- `$this->truncateTextColumn($columns, $length)` - Truncate multiple text columns
- `Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly()` - Get setup user
- `Tinebase_Core::getDb()` - Get database adapter
- `Tinebase_Application::getInstance()->getApplicationByName($_name)` - Get application model

## Field Types for Schema Declarations

Use `Setup_Backend_Schema_Field_Xml` with XML declarations:

```xml
<field>
    <name>column_name</name>
    <type>integer</type>
    <notnull>true</notnull>
    <default>0</default>
</field>
```

Supported types: `integer`, `text`, `string`, `boolean`, `datetime`, `float`, `decimal`

Common attributes:
- `<name>` - Column name (required)
- `<type>` - Data type (required)
- `<length>` - String/text length
- `<notnull>` - `true` or `false`
- `<default>` - Default value
- `<default>NULL</default>` - For nullable columns

## Best Practices

1. **Idempotency**: Always check if changes are needed before applying (e.g., `columnExists()`)
2. **Version tracking**: Always call `addApplicationUpdate()` at the end of each update method
3. **Table versioning**: Update table versions when schema changes occur
4. **Priority ordering**: Structure updates by priority - schema first, then data
5. **Error handling**: Use try/catch for non-critical operations; let critical failures throw
6. **Logging**: Use `Tinebase_Core::getLogger()` for important migration steps
7. **One update = one concern**: Keep each update method focused on a single change
8. **Update key constants**: Use class constants for update keys to avoid typos
9. **Check existing updates**: Use `hasApplicationUpdateRan()` to skip already-applied updates

## Creating a New Update Script

1. **Determine the version**: Check `setup.xml` for current version
2. **Create the file**: `tine20/{AppName}/Setup/Update/{Version}.php`
3. **Define update constants**: One per update method
4. **Register in `$_allUpdates`**: Map priorities to update methods
5. **Implement update methods**: Each handles one migration task
6. **Call `addApplicationUpdate()`**: At the end of each method with the correct version
7. **Update `setup.xml`**: Bump the version number if needed

## Testing Updates

Run updates via the tine-dev console:

```bash
cd $TINE_DOCKER_PATH
./console tine:update
```

Or run setup.php directly:

```bash
cd tine20
php setup.php --update
```

## Related Files

- `tine20/Setup/Update/Abstract.php` - Base class with common methods
- `tine20/Setup/SchemaTool.php` - Doctrine schema management
- `tine20/{AppName}/Setup/setup.xml` - Application metadata and version
- `tine20/{AppName}/Setup/Initialize.php` - Initial installation (separate from updates)
