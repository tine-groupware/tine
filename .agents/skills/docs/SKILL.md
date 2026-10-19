---
name: docs
description: >-
  Create and maintain human readable documentation. Auto-invoke when user mentions docs, documentation or manual.
   Do NOT load for api docs or api reference or docs blocks in code.
license: AGPL-3.0
metadata:
  author: Cornelius Weiss
  version: "0.1"
---

# Docs

## Tooling

* Create and maintain human readable documentation.
* All contents is in /docs folder and below
* Uses mkdocs to generate the docs
* mkdocs.yaml is in the root of the project
* local build is part of the dev setup activated by 'docs' in pullup.json config
* build is done by local docker container
* Dockerfile for the container is in the /docs folder
* build of docs can be accessed at https://docs.local.tine-dev.de/

### Screenshots

* Screenshots of the docs are captured by the end-to-end tests and uploaded in on other process.
* Screenshots are available in 5 different sizes
* Screenshots are available in dark and light mode

## Personas

Docs are structured into main subfolders for the following personas: 
* `./admins` Adminsitrators having full rights in the software. Sometimes also called KeyUsers. Normally they don't have 
  root or shell access. The often define **how** users should use the software. Admins might not be albe to configure
  all documented features due to missing rights or license limitations.
* `./developers` Developers of the software system
* `./operatos` People operating the software. Having shell access with full rights in the hosting environment.
* `./users` End users without special technical knowledge. Users might not be able to use all the documented features 
  due to missing rights or license limitations.

## User Manual

* A special section of the user documemtation is the manual (`./users/manual`)
* The manual is available via *help* and *manual* buttons within the software.
* When the manual is opened, the software computes a `context` where the user is located in the software.
* This context is used to jump to the most appropriate entry point in the user manual

### Contexts in documentation

* Contexts are invisible markers in the documentation articles
* See `docs/hooks/build_context_map.py` for details:
  * In text: <a id="ctx:Addressbook.EditDialog.Contact.AttachmentsGrid"></a>
  * In headings: ### Anhänge verwalten { #attachments data-ctx="/Addressbook/EditDialog/Contact/AttachmentsGrid" }
* In the build process the file `context-map.json` is created which maps contexts to articles/anchors.
* The user manual searches for the nearest existing context in the map.

### Contexts in code

* Contexts are defined in javascript code joining the `canonicalName` of each component in the componten tree.
* see `Tinebase/js/CanonicalPath.js` for details.
* Example:`/Addressbook/EditDialog/Contact/AttachmentsGrid`:
  * `/Addressbook/EditDialog/Contact` - this canonicalName is created by `Tinebase/js/widgets/dialog/EditDialog.js`
    * it means user is in applicatin `Addressbook` and in dialog `EditDialog` for the recordClass `Contact`
  * `AttachmentsGrid` is a sub component (tabPanel) `Tinebase/js/widgets/dialog/AttachmentsGridPanel.js`

### Article order

* Each `Application` should have a user manual article.
* As different branches contain different applications, there is no static file to include articles and define order.
  * Order of articles is defined by names. Naming convention of articles is `<XX>_<Appname>.md` where `XX` are two letters
    defining the order.

## Article overview
* in the build process the file `index.md` is created which has entry point tiles for all apps/articles
* Each tile has the app icon for easy reference.

