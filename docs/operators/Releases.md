# Release Matrix

| | weekly | monthly | beta  | be | lts | customer | nigthly |
|---|---|---|---|---|---|---|---|
releses	| next	| next | beta	| be	| lts	| customer	| every branch is possible |
releses eg.	| main	| main | 2024.11	| 2023.11	| 2022.11	| metaways/2024.11	| pu/fix/packaging |
git tag	| `weekly-<date>.<counter>[*]`	| `main-<date>.<counter>[*]` | `<tine version>-<data>.<counter>[*]` | `<tine version>.<counter>[*]`	| `<tine version>.<counter>[*]`	| `<customer>-<tine version>-<date>.<counter>[*]`	| not tag =, but version is `nightly-<branch with / replaced by - >-<year>.<month>.<day>-<short commit sha>` |
tag eg	| weekly-2024.6.30.1	| main-2024.2.15.2 | 2024.11-2024.8.7.1 | 2023.11.4	| 2022.11.4	| metaways-2024.11-2024.01.29.3pl16	| nightly-pu-fix-packaging-2024.03.28-g19ebe82e |
triggerd	| by schedule - weekly	| by tag (conventionally every 3. Thursday of a month) | by tag (conventionally every 3. Thursday of a month) | by tag (conventionally every 3. Thursday of a month)	| by tag (conventionally every 3. Thursday of a month)	| by tag / by schedule (depends on the customer)	| schedule / merge request |
github release	| as pre release	| no | as pre release	| as latest	| no	| no	| no |
dockerhub tag	| weekly, git tag	| no | tine version without .11, git tag | latest, tine version without .11, git tag	| no	| no	| no |
vpackages	| no	| no | no	| tine20.com/maintance, set current link	| tine20.com/maintance	| customer repo, set current link, optional	| for debug purposes |
gitlab packages	| weekly	| monthly | no	| tine20.com	| no	| customer name / configurable | no |
| customer registry tag	| no	| no | no | no	| no	| git tag, <customer>-<tine version withouth .11>, latest	| no |
| version check | no | no | no | yes | no | no | no
| tine edition | be (but with bete license) | be | be | be | be | be | - |

## Open changes
+ weekly tag: change `weekly-<year>.<weeknumber>.<counter>` to `weekly-<date>.<counter>[*]`
+ monthly releses e.g.: change 2024.11 to main
+ monthly tag: change `<tine version>-<date>.<relasecounter>[*]` to `main-<date>.<counter>[*]` (releasecounter == counter)
+ add "beta" support to our ci