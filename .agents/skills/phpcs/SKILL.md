---
name: phpcs
description: >-
  Run PHP_CodeSniffer (phpcs) to check PSR-12 code style on changed PHP files. Use after
  editing PHP to verify code style before committing.
license: AGPL-3.0
metadata:
  author: Philipp Schüle with OpenCode / Qwen 3.8
  version: "1.0"
---

# Run phpcs

Check the PSR-12 code style of the PHP files you changed. Always go through the project's
`phpcs` composer script instead of calling `vendor/bin/phpcs` with your own flags — the
script pins the exact `--standard`, `--exclude`, `--ignore` and `--basepath` the project
uses, so results match CI.

## Command

Run from the `tine20/` source directory:

    composer run phpcs <file>

- `<file>` is relative to `tine20/`, e.g. `Setup/Backend/Mysql.php`.
- Omit `<file>` to lint the whole tree (the script defaults to `.`).
- Multiple files: `composer run phpcs Setup/Backend/Mysql.php Setup/Controller.php`.

The script lives in `tine20/composer.json` (`scripts.phpcs`). Do not duplicate its flags.

## Requirements

phpcs needs the PHP extensions `tokenizer`, `xmlwriter` and `SimpleXML`. If the host PHP
is missing them (phpcs prints "requires the tokenizer, xmlwriter and SimpleXML
extensions"), run it inside the tine-dev `web` container instead (below).

## In the tine-dev docker setup

Run phpcs inside the `web` container, which has composer + the required PHP extensions.
Run from your tine-dev checkout (where `docker-compose.yml` is):

    docker compose -f docker-compose.yml exec -T --user tine20 web \
        sh -c "cd /usr/share/tine20 && composer run phpcs <file>"

The container's `/usr/share/tine20` is a bind mount of the local `tine20/` source dir, so
your changes are picked up. File paths stay relative to `/usr/share/tine20`. Tests live
outside it under `/usr/share/tests`, so use a `../tests/...` path, e.g.:

    docker compose -f docker-compose.yml exec -T --user tine20 web \
        sh -c "cd /usr/share/tine20 && composer run phpcs ../tests/setup/Setup/ControllerTest.php"

## Interpreting the output

- phpcs exits non-zero when it finds errors or warnings.
- Only fix violations on lines you changed. These files carry many pre-existing
  violations (trailing whitespace, `TRUE`/`FALSE`/`NULL`, `else if`, long help-text
  lines, ...). Do not reformat unrelated code.
- Lines marked `PHPCBF CAN FIX ... AUTOMATICALLY` are auto-fixable; apply them with
  `composer run phpcbf <file>` when appropriate.
- Keep lines under 120 characters and avoid inline control structures (PSR-12).
