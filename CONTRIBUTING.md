# Contributing

**AI coding agents:** see [AGENTS.md](AGENTS.md) at the repository root for project-specific guidance (development setup, tests, conventions, and workflow).

## Write Code

You might find this project helpful for setting up a development environment for tine Groupware:
https://github.com/tine-groupware/tine-dev

* Please make sure that there aren’t existing pull requests attempting to address the issue mentioned
* Check for related issues on the issue tracker
* Non-trivial changes should be discussed on an issue first
* Let us know you’re working on the issue (by using the
 [Kanban board](https://github.com/orgs/tine-groupware/projects/1))
* Develop in a topic branch, not main
* Provide useful pull request description
* Write a good description of your PR
* Link to the Github issue in the description
* See below for avoiding merge conflicts in the first place

The default workflow for Github Pull Requests applies for tine Groupware, too.

see https://help.github.com/articles/about-pull-requests/

### Sign the CLA

This can be done directly in the Pull Request, thanks to https://cla-assistant.io/

### Writing Tests

For PHP code, write [phpunit](https://phpunit.de/) tests (see https://github.com/tine-groupware/tine/tree/main/tests/tine20)
For JavaScript unit tests, write [Jest](https://jestjs.io/) tests (see https://github.com/tine-groupware/tine/tree/main/tests/js)
For end-to-end tests, write [Jest + Puppeteer](https://github.com/tine-groupware/tine/tree/main/tests/e2etests) tests

### Commit Message Guidelines

tine Groupware uses [Conventional Commits](https://conventionalcommits.org/) style commit messages:
 
    <type>(<scope>): MESSAGE
    
    [optional body]
    
    [optional footer]

Example:

    feature(Addressbook): adds type-ahead to some contact fields
    
    * company
    * unit
    * address data
    
    closes #289

Another:
    
    fix(Phone): improve handling of empty phone numbers

    ... when looking for the matching contact

See also the [AngularJS commit guidelines](https://github.com/angular/angular/blob/22b96b9/CONTRIBUTING.md#-commit-message-guidelines).

\<type\> can be one of the following:

- tweak
- hack
- fix
- feature
- build
- docs
- perf
- refactor
- style
- test
- config
- script
- text

Some example \<scope\>s:

- APPNAME like Addressbook, Calendar, Tasks
- Tests / Unittests
- Cli
- Import, Export
- Setup

You should reference a GitHub issue (if exists) like this:

    See #1234
    
If the commit closes an issue, it should be done like this:

    Closes #1234
    
(or any other "closing" keyword - see https://help.github.com/articles/closing-issues-using-keywords/ for reference)

If the commit reverts a previous commit, it should begin with revert:, followed by the header of the reverted commit.
In the body it should say: "This reverts commit <hash>", where the hash is the SHA of the commit being reverted.

We also use some annotations to mark commits for documentation follow-ups:

- @usermanual (User manual needs to be updated because of this commit)
- @releasenotes (RELEASENOTES need to be updated because of this commit)

If you use ai coding agents, please add the following annotation:

- @ai supported by qwen 3.6

## Merge Conflicts

A merge conflict occurs when two branches modify the same lines of a file and git cannot merge them automatically. You must resolve the conflict by hand, keeping the parts you want and removing the conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`).

### Ground rules

* Never commit directly to `main` or any release branch. Always work on a topic branch and open a pull request.
* For new features and general development: create the topic branch from `main` and open the PR targeting `main`.
* For bug fixes and work on older release branches (e.g. `2025.11`): create the topic branch from that release branch and open the PR targeting it.

### Preventing conflicts

Before opening a PR (and before pushing updates to an existing one), sync your topic branch with the branch it targets:

```sh
git fetch origin
git checkout main
git pull --rebase
git checkout my-topic-branch
git rebase main
```

Resolve any conflicts that appear, then push. Rebase early and often — the longer a topic branch stays out of sync, the bigger the conflicts get. (Optional convenience: `git config pull.rebase true` makes `git pull` rebase instead of creating merge commits.)

### Case 1: topic branch from `main`, PR targeting `main`

This is the most common case. If the PR reports merge conflicts, rebase your topic branch onto the current `main`:

```sh
git fetch origin
git checkout my-topic-branch
git rebase main
# resolve conflicts, then:
git add <resolved files>
git rebase --continue
git push --force-with-lease origin my-topic-branch
```

* `--force-with-lease` is the safe variant of force-push: it refuses to overwrite the remote branch if it has changed in the meantime. Only force-push your own topic branch — never `main` or release branches.
* If the rebase gets messy, `git rebase --abort` restores the state from before the rebase.
* Alternative: `git merge main` into the topic branch instead of rebasing. This avoids force-push but adds a merge commit. Rebase is the preferred approach here.

### Case 2: work on a release branch (e.g. `2025.11`)

1. Checkout the release branch, create a topic branch from it, push, and open a PR targeting the release branch.
2. After the PR is merged, the CI pipeline automatically merges the change forward through each intermediate branch until it reaches `main`.

This forward-merge can produce conflicts even if the topic branch itself had none, because the same files have diverged between the release branch and `main` independently of your change. This is especially common for the e2etests: `main` has moved on (new node packages, new helpers in the e2etest libs, switch to ES modules, ...), so the test files on both branches are only partly compatible.

**Rules for e2etests in this situation:**

* Do not change the e2etests on `main` unless the changes in the topic branch also fix the e2etests on `main`.
* When resolving conflicts in test files, keep as much of the `main` version as possible (ES module syntax, new helpers, current package versions) and only port the functional test changes from the release branch.

**Resolving the forward-merge conflict** (do not revive the deleted topic branch):

1. Look at the failing pipeline job and note the two branches involved (e.g. `2025.11` and `main`).
2. Fetch both branches.
3. Create a new branch from the target branch (`main`) with a name that includes `merge` and the PR id, e.g. `merge-1234-2025.11-into-main`.
4. Merge the originating branch (`2025.11`) into it.
5. Resolve the conflicts (see the e2etest rules above).
6. Push the branch and open a PR targeting `main`.
7. Do not squash the commits and do not rebase the new branch when merging the PR.

In case the PR pipeline fails and only the job "prevent_dot_agents_changes" did not succeed:
Simply add a label "allow-failure-prevent-dot-agents-changes" to the PR and ignore the failed job.

### After resolving conflicts (all cases)

* Re-run the tests that cover the code you touched (PHPUnit via the tine-dev console, Jest, ...).
* In particular, verify that the e2etests pass locally. They are the most likely to fail in CI after a conflict resolution.

### Other cases and tips

* **Two open PRs touching the same files:** coordinate with the other author. Merge the first PR, then rebase the second one onto the updated `main`.
* **Lock files** (`package-lock.json`, `composer.lock`): do not merge them by hand. Resolve the conflicts in `package.json` / `composer.json` first, then run `npm install` / `composer update` to regenerate the lock file.
* **Generated files** (build output, language files): pick one side and regenerate. Never hand-edit `.po` translation files — they are managed via Transifex.
* **Take one side wholesale:** `git checkout --ours <file>` / `git checkout --theirs <file>`. Watch out: during a *rebase*, "ours" is the branch you rebase onto (`main`) and "theirs" is your topic branch — the opposite of what most people expect. During a *merge*, "ours" is the branch you are currently on.

## Reporting Bugs

Before submitting an issue please check that you’ve completed the following steps:

* Made sure you’re on the latest version
* Used the search feature to ensure that the bug hasn’t been reported before

Bug reports should contain the following information:

* Summary: A brief description
* Steps to reproduce: How did you encounter the bug? Instructions to reproduce it
* Expected behavior: How did you expect it to behave?
* Actual behavior: How did it actually behave?
* References: Links to any related tickets or information sources.
* If possible, add log file snippets (tine Groupware bog, webserver logs, browser console logs, ...)
* If possible, attach visual documentation of the bug. Screenshots, video and/or animated gifs.

## Translations

tine Groupware manages translations at [Transifex](https://app.transifex.com/tine/groupware/dashboard/),
a free Web-Service for open source projects.

## Write Documentation

_TODO add more_

### Coding Standards

For PHP Code, we use the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://www.php-fig.org/psr/psr-2/) Coding Standards.

## Asking and Answering Questions

If you have a question, please open an issue or a [discussion](https://github.com/orgs/tine-groupware/discussions).
We will put the label "Question" on it. Feel free to answer questions of other users.

Questions on GitHub can be asked in german or english.

## Additional Resources

* [Guidelines for CONTRIBUTING](https://help.github.com/articles/setting-guidelines-for-repository-contributors/)
* [AngularJS Commit Message Guidelines](https://github.com/angular/angular/blob/22b96b9/CONTRIBUTING.md#-commit-message-guidelines)
