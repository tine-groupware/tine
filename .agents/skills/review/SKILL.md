---
name: review
description: >-
  Review a merge request of tine: verify the description against the code, findings with situation and suggestion,
  one thread per finding plus a summary, in English. Use when asked to review a merge request, or to answer review
  threads on an own merge request.
license: AGPL-3.0
metadata:
  author: Cornelius Weiss with Claude Code
  version: "1.0"
---

# Review a merge request

How to read the merge request and post threads depends on the tools of the dev setup (gitlab API / MCP, web UI).

## Read

1. Title, description, target branch, changed files, the diff file by file (not one huge diff), existing threads.
2. The code around the changes in the target branch, not only the changed lines: callers, other users of the
   changed code (`git grep` the class / function), how values are compared / normalized, the css of the elements
   involved.
3. Is the target branch right (skill `contributing`, "Target branch")? Is the change complete for all places that
   need it (other apps / dialogs / models)?

## Check

* Verify the claims of the description against the code.
* Reproduce the bug or the fix when it is cheap (a small script, one test) and say for each finding whether it was
  reproduced or only derived from the code.
* Typical things to look for: missing ACL / grant checks, data loss on save (fields emptied, dependent records),
  update scripts (idempotent, big tables, the update number / version of the app), migrations of stored values,
  translations (new strings in the .po files), races (async without waiting, fixed sleeps in tests), copied
  records (what must not be copied), secrets / hostnames / customer data in the code.

## Write

* In English - not all developers speak German. Replies in an existing thread: in the language of that thread.
* Each finding: what goes wrong, in which situation (concrete inputs / state), and a concrete suggestion - code if
  short. No style nitpicks without a reason.
* One thread per finding, at the line (diff position of the changed line).
* One summary: overall assessment, the list of findings, what was checked (and what not).
* Mark posts written by an agent, e.g. "Review (Claude Code, on behalf of <user>)" - they appear under the user's
  account.
* Never approve or merge, never push to branches of others.

## Answer review threads (own merge request)

* Reply with what was changed and in which commit, then resolve the thread.
* Don't resolve threads on merge requests of others, and don't resolve a thread when the reviewer has to decide.
