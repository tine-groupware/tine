# Build from GIT

## 1. Build development version from source

You can download the tine source code using **git**, see example below. If you are looking for a particular release,
choose `git clone --depth 1 git@github.com:tine-groupware/tine.git`.
Also you'll require dependencies: Run **composer** (from `tine20/`). To build the JavaScript files (GUI) you need
the **submodules** for the icon-set (if you use git, see example below, from _base dir_) and **npm**
(see [Development-Setup](../developers)); build is done by **phing** & npm.

```sh
git clone git@github.com:tine-groupware/tine.git
cd tine
git submodule update --init
cd tine20
composer install --prefer-source --no-interaction
./vendor/bin/phing
cd Tinebase/js
npm run build
```

Please consider also both steps below!

## 2. Update database if necessary

If your previously installed or checked out release was below recent version, you most likely need to adjust the
database (aka update). Running update if not necessary is harmless (nothing happens).

In **tine20/** run `php setup.php --update`.

## 3. Work with the installation

After you built tine to release code or *to work with cli only (no web-gui)* you
must add the following to your *config.inc.php* file:

```php
  'buildtype' => 'RELEASE', // or 'DEBUG'
```

If you want to work in the webpack development environment again just change it to:

```php
  'buildtype' => 'DEVELOPMENT',
```
