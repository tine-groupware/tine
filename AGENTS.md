# Agent guidance for tine Groupware

Context for AI coding agents working in this repository. Prefer this file and linked docs over guessing project conventions.

## Project overview

**tine** is open-source groupware and CRM (calendar, contacts, mail, tasks, CRM, and more). The server is primarily **PHP**; the web UI uses **JavaScript** (ExtJS legacy, Vue components, webpack). License is mainly **AGPLv3** — customizations must remain AGPL-compatible unless you have a separate license agreement.

| Resource | URL |
|----------|-----|
| Homepage | https://www.tine-groupware.de |
| Documentation | https://docs.tine-groupware.de/ |
| Issues | https://github.com/tine-groupware/tine/issues |
| Dev environment | https://github.com/tine-groupware/tine-dev |

## Repository layout

| Path | Purpose |
|------|---------|
| `tine20/` | Main application source (PHP apps, JS under `Tinebase/js/`, `library/`) |
| `tests/tine20/` | PHPUnit tests (mirror app structure) |
| `tests/js/` | JavaScript unit tests (Jest config, Karma) |
| `tests/e2etests/` | End-to-end tests (Jest + Puppeteer) |
| `docs/` | MkDocs documentation (developers, operators, admins) |
| `scripts/` | Packaging, Ansible, git hooks, utilities |
| `ci/` | GitLab CI definitions |
| `.junie/` | Junie agent notes (e.g. testing); `memory/` and `plans/` are local-only (gitignored) |
| `.github/workflows/` | GitHub Actions (PHPUnit on PRs) |

PHP class naming follows `{App}_{Layer}_{Name}` (e.g. `Calendar_Controller_Event`, `Addressbook_Model_Contact`). Tests use the same prefix with a `Test` suffix (e.g. `Admin_Controller_SchedulerTaskTest`).

Apps: Addressbook, Calendar, Felamimail, Tasks, Timetracker, Projects, Crm, Filemanager, Bookmarks, SSO, GDPR, Admin, CoreData, EFile, ExampleApplication, HumanResources, Inventory, MatrixSynapseIntegrator, OnlyOfficeIntegrator, Poll, Purchasing, Sales, Scheduler, SaasInstance, SimpleFAQ, Setup, Tasks, Tinebase, Courses, CrewScheduling, ActiveSync, EventManager, DFCom.

## Development environment

**Do not use this repo alone for a runnable stack.** Use the separate **[tine-dev](https://github.com/tine-groupware/tine-dev)** (also called tine-docker / docker-dev) Docker setup. It provides the `console` CLI used for tests, npm, and common dev tasks.

Set the path to your tine-dev checkout:

```bash
export TINE_DEV_PATH=/path/to/your/tine-dev
```

On this sandbox, the path is `/tine-dev` when that repo is present.

### Build from source (summary)

See `docs/developers/build.md` and `README.md` § dev setup.

```sh
git submodule update --init
cd tine20
composer install --prefer-source --no-interaction
./vendor/bin/phing
cd Tinebase/js
npm run build
```

Database updates: `php setup.php --update` from `tine20/`. For release/CLI-only mode, set `'buildtype' => 'RELEASE'` in `config.inc.php`; use `'DEVELOPMENT'` for webpack dev.

### Supported stack

- PHP 8.1–8.3
- MySQL 8.0.12–8.0.44 / MariaDB 10.4–12.2
- Apache 2.4 / Nginx 1.26
- Redis 7.0 / Valkey 9.0
- Node.js 10.15.3 (`.nvmrc`)

## Running tests

PHPUnit and JS tests are normally run **via tine-dev’s `console`**, not by invoking PHPUnit/npm directly on the host (unless you replicate CI locally).

### PHP (PHPUnit)

Canonical docs: `docs/developers/server/phpunit.md` and `.junie/testing.md` (keep in sync when editing).

```bash
cd $TINE_DEV_PATH
./console tine:test <TestClassName>::<TestMethodName>
```

Example:

```bash
cd $TINE_DEV_PATH
./console tine:test Admin_Controller_SchedulerTaskTest::testCreateSchedulerTask
```

- Run from `$TINE_DEV_PATH`.
- `tine:test` wraps PHPUnit.

**CI (without Docker):** GitHub Actions installs tine in `tine20/`, then runs `tests/tine20/../../tine20/vendor/bin/phpunit --color GithubTests.php` (see `.github/workflows/php-unit-test.yml`).

From `tine20/` you can also use Composer scripts: `composer test` / `composer phpunit` (phing-based).

### JavaScript (Jest)

See `tests/js/jest/Readme.md`.

```bash
cd $TINE_DOCKER_PATH
./console src:npm 'test'
./console src:npm 'test -- Array.test.js'
./console src:npm 'test -- Array.test.js --testNamePattern "Array.diff"'
```

**Jest conventions:** pure logic tests, no DOM; avoid global pollution (`Tine`, `Ext`, `_`, etc.) in tests and in code under test.

Karma watch mode is available via npm scripts in `tine20/Tinebase/js/package.json` (`test:watch`).

### E2E

See `tests/e2etests/README.md`. Requires a running instance (e.g. `TEST_URL`).

## Code quality

### PHP

- **Style:** PSR-12 via PHP_CodeSniffer (with project exclusions for legacy Zend-style class/method naming).
- From `tine20/`:
  - `composer phpcs` — lint
  - `composer phpcbf` — auto-fix where possible
  - `composer rector` — refactors per `.rector/rector.php`
- **Static analysis:** PHPStan (`phpstan.neon`, baseline in `phpstan-baseline.neon`).
- **Contributing standard:** PSR-1 / PSR-2 referenced in `CONTRIBUTING.md`.

### JavaScript

- ESLint (Standard-derived config) in `tine20/Tinebase/js/` (`.eslintrc.js`).
- `.editorconfig`: 2 spaces for `*.vue`, `*.es6.js`, `*.spec.js`, `*.mjs`; LF line endings; UTF-8; trim trailing whitespace; insert final newline.

## Contributing expectations

Read `CONTRIBUTING.md` before opening PRs.

- Discuss non-trivial changes in an issue first.
- Use **topic branches**, not `main`.
- Sign the **CLA** (via cla-assistant.io on the PR).
- **Commit messages:** [Conventional Commits](https://www.conventionalcommits.org/) — `<type>(<scope>): message` with optional body/footer.
  - Types: `tweak`, `hack`, `fix`, `feature`, `build`, `docs`, `perf`, `refactor`, `style`, `test`, `config`, `script`, `text`
  - Scopes: app names (`Calendar`, `Addressbook`, …), `Tests`, `Cli`, `Import`, `Export`, `Setup`, etc.
  - Link issues: `See #1234`, close with `Closes #1234`
  - Doc follow-ups: `@usermanual`, `@releasenotes`
- **Tests:** add PHPUnit tests for PHP changes (`tests/tine20/`); add Karma/Jest tests for JS where applicable.

## Documentation map (for agents)

| Topic | Location |
|-------|----------|
| Developer index | `docs/developers/index.md` |
| PHPUnit | `docs/developers/server/phpunit.md` |
| Models / expanders | `docs/developers/server/models.md` |
| JSON API | `docs/developers/server/jsonApi.md` |
| Build | `docs/developers/build.md` |
| Translations | `docs/developers/translations_doku.md` |
| ADRs | `docs/developers/adr/` |
| Operator / Docker | `docs/operators/docker/` |

Published docs: https://docs.tine-groupware.de/

## Translations

Managed on [Transifex](https://app.transifex.com/tine/groupware/dashboard/). Do not hand-edit `.po` files when tooling exists — use `console src:langHelper` (see `docs/developers/translations_doku.md`).

Extraction: `./console src:langHelper -- '-u --app Calendar -l de'`
Merge from Transifex: `./console src:langHelper -- '--txmerge --app Calendar -v -l bg'`
Edit with poedit, then remove line numbers: `./console src:langHelper -- '-u -l de'`

## Files agents should respect

- **`.aiignore`** (also **`.cursorignore`**): same syntax as `.gitignore` — excludes noisy paths (logs, `scripts/sql`, large mail test fixtures). Do not assume ignored paths are safe to skip for *security*; they are mainly for context size.
- **`.junie/`**: project-specific agent notes; only `testing.md` is tracked. `memory/` and `plans/` are developer-local.

## Agent workflow checklist

1. Read relevant code and `docs/developers/` pages before changing behavior.
2. Match existing patterns (naming, layers: `Model`, `Controller`, `Frontend`, etc.).
3. Keep changes focused; avoid unrelated refactors.
4. Run targeted tests via `cd $TINE_DEV_PATH && ./console tine:test …` when tine-dev is available.
5. Run `composer phpcs` on touched PHP under `tine20/` when possible.
6. Do not commit unless the user asks; follow conventional commit format when they do.
7. Do not commit secrets (`config.inc.php` with credentials, license keys, etc.).

## Related repositories

- **tine-dev** — Docker dev stack and `console` CLI
- **tine-groupware/tine** — this monorepo (server + tests + docs)

When test instructions in `.junie/testing.md` and `docs/developers/server/phpunit.md` diverge, align them in the same change.
