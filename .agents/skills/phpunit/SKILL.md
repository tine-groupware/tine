---
name: phpunit
description: >-
  Run PHPUnit tests (normal app tests and Setup tests) via the tine-dev console. Use to
  verify a PHP change against its test suite.
license: AGPL-3.0
metadata:
  author: Philipp Schüle with OpenCode / Qwen 3.8
  version: "1.0"
---

# Run PHPUnit tests

Tests are run through the tine-dev `console`, which execs PHPUnit inside the running
`web` container. Run these commands from your tine-dev checkout (where `docker-compose.yml`
is); the stack must be up. There are two commands, depending on where the test lives:

## Normal app tests — `tine:test`

For tests under `tests/tine20/` (Addressbook, Calendar, Admin, ...). The path is relative
to `tests/tine20/`. Give either the file path or the underscore class name (the latter is
automatically converted to a path):

    ./console tine:test Addressbook/Frontend/JsonTest -f testGetAllContacts
    ./console tine:test Admin_Controller_SchedulerTaskTest::testCreateSchedulerTask

- `Class::method` or `-f method` runs a single test method; omit it to run the whole file.
- `./console tine:test AllTests` runs the entire suite.

## Setup tests — `tine:setuptest`

For tests under `tests/setup/` (the `Setup` app). The path is relative to `tests/setup/`
and must include the `.php` extension (there is no underscore-to-path conversion here):

    ./console tine:setuptest Setup/ControllerTest.php::testBackupExcludesConfigKeys
    ./console tine:setuptest Setup/Backend/MysqlTest.php

## Options (both commands)

- `-f, --filter=NAME` — run only matching test method(s)
- `-s, --stopOnFailure` — stop on the first failure
- `-e, --exclude=GROUP` — exclude a test group (repeatable)
- `-b, --branch=BRANCH` — run against a specific tine branch

## Notes

- A `::method` in the path is split off as the filter, so you do not also need `-f`.
- The container's `/usr/share/tests` is a bind mount of the local `tests/` dir, so new or
  changed tests are picked up without a rebuild.
- PHPUnit prints `OK (n tests, m assertions)` on success; on failure the console prints
  `TESTS FAILED` and returns a non-zero exit code.
