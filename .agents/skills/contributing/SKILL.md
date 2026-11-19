---
name: contributing
description: >-
  How a change gets into tine: target branch (oldest supported major, merged forward), own topic branch, which
  tests to run locally, commit, merge request description, and what to do when the pipeline of a merge request
  failed (rebase first, random fails are fixed). Use when fixing a bug / implementing a feature, before
  committing, when creating a merge request, or when a pipeline failed.
license: AGPL-3.0
metadata:
  author: Cornelius Weiss with Claude Code
  version: "1.0"
---

# Contributing a change

Tool independent - how to run tests, push or open a merge request depends on the dev setup (e.g. tine-dev
`console`, or the tools of an AI coding setup). Conventions for commit messages: AGENTS.md, "Contributing
expectations". Reviews: skill `review`.

## Target branch

* Find out which branches have the bug / need the change. A feature may exist only on newer branches, e.g.
  `git log origin/2026.11..origin/main -S<symbol>`. Ask the user if unclear.
* The target is the **oldest supported major branch** that needs it (e.g. `2025.11` for a bug that exists there).
  Major branches are merged forward into the newer ones (`2025.11` → `2026.11` → `main`), so the change reaches
  them without cherry-picks. Never target a newer branch to "save" the merge.
* Work on an own topic branch on top of it (`pu/<you>/<topic>`), never directly on a major branch or on someone
  else's branch. For a fix on top of someone else's merge request: an own branch on top of theirs, merge request
  into their branch.
* Changes under `.agents/` (these skills): in the oldest major branch that has the file, merged forward. The CI job
  `prevent_dot_agents_changes` fails merge requests that modify existing `.agents` files on a newer branch; if the
  file exists only from that branch on, set the label `allow-failure-prevent-dot-agents-changes` and say why in the
  description. New files are not affected.

## Which tests to run locally

The CI runs the full suites on every merge request pipeline. Locally run only what the change needs:

* the new / changed tests (`--filter <testMethod>`), and the tests of the code you changed directly (the test
  methods of the class / feature, an e2e test of the dialog)
* not whole test classes, app suites or all e2e tests for a simple fix / feature - they take minutes, and failures
  that come from the environment (e.g. external services, eDocument validation) cost time without telling anything
  about the change
* broader runs only when the task needs it (changes in shared code like Tinebase, the filesystem, the controller /
  model base classes, update scripts touching many records, a refactoring) or the user asks for it
* a test that fails: check it against the target branch before blaming the change (e.g. `git stash`, run that one
  test, `git stash pop`), and say in the merge request which local failures exist without the change
* add tests for the change where the repo has them (PHPUnit for PHP, e2e / Jest for JS)

## Commit

* Conventional commits (AGENTS.md), the subject says what changed for the user, the body explains the why (cause,
  and which commit introduced it if known).
* One commit per logical change; fixups of an unpushed / unmerged own branch: amend or squash.
* Only when the user asks for it. Never commit secrets, temporary scripts, screenshots or local config.
* The repo is public (GitHub): no internal hostnames / URLs, internal project paths, customer names or customer
  data in code, tests, config defaults, commit messages or skills.

## Merge request

* Title = subject of the (main) commit, target = the target branch, remove the source branch after merge.
* Description in English (not all developers speak German):
  * what was wrong / is new
  * the cause (and which commit introduced it, if known)
  * the fix / the implementation, and decisions made on the way
  * what was tested: reproduced or only derived from the code, before / after
  * what is known but not done (follow-ups, other branches)
* Assign it to the user who asked for it; reviewers only when the user names them.
* Text written by an agent under the user's account: mark it (e.g. "Claude Code, on behalf of <user>").

## Failed pipeline

1. **Rebase first:** rebase the branch onto the current target branch and push it (force with lease) - that starts
   a new pipeline. Failures fixed on the target branch meanwhile are gone then. Only own branches; for a merge
   request of someone else tell the user instead. Check that the rebased patch equals the original
   (`diff <(git show <old>) <(git show HEAD)`).
2. **Find the failing test:** the failed jobs of the pipeline, the summary at the end of the job log ("Summary of
   all failing tests" for e2e) or the test report of the pipeline.
3. **Related or random?** A failure in code the merge request doesn't touch, which also fails or passes on other
   pipelines with the same code, is a random fail (flaky test or a race in the code).
4. **Random fails are fixed, not retried:** find the cause - in tests typically fixed sleeps, clicks before a load
   / mask finished, waiting for the wrong thing; or a race in the code - and fix it on an own branch of the oldest
   major branch that has the test, with its own merge request (not in the merge request that happened to hit it).
   Reproduce the race if possible (e.g. delay the request via puppeteer request interception) and show it fails
   before and passes after. Mention the fix in the original merge request.
