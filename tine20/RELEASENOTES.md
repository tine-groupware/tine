
TINE RELEASENOTES
=====================
                    
  Release:     Ellie (2023.11)
  Last change: 2023-11-06

# GENERAL CHANGES (Administrative)

## PHP 7.4 Support dropped

## Full support for PHP 8.1

## Use mkdocs for documentation

see [https://tine-docu.s3web.rz1.metaways.net/]

# GENERAL CHANGES (User Interface)

## New messagebox with personas

see [https://mastodon.social/@CorneliusWeiss@metaways.de/110616973937301783]

New faces in the messageboxes. The refactoring of the user interface is proceedings,
 now we addressed the mini-dialogues and added personas to them.

Personas also have different/randomized skin colors.

## Dark mode

Dark mode is auto-detected from operating system of the user. Color scheme can also be adjusted by the user.

## East panel grid layout

Grid details panel can be moved to the right side of the grid.

## Related Record filters

Major improvements for filtering related records 

# ADMIN / OPERATION

## SSO with SAML2/OpenIDConnect (Identity Provider + Relaying Party + Proxy)

- tine can be IDP for 3rd party apps
- relaying party: users can be configured to have own identity providers (i.e. Google, MS, ...)
- proxy: session is in tine, but other IDP

## Configurable templates for usernames, display/full names and email addresses of new users

## Multiple improvements in user import

## New scheduler jobs

For example the auto-invoicing job is now handled by the scheduler and does not need to be defined as a cronjob.

## Allow IMAP master access during role change (Dovecot IMAP SQL backend required)

E-Mail access for role-change has been added. This makes supporting users with their email related problems much easier.

## XPROPS editor for user, groups and roles

## Allow to rate-limit JSON API methods

## CLI: add --createmissingtables to restore lost tables

# ADDRESSBOOK

## New addressbook fields

see [https://mastodon.social/@CorneliusWeiss@metaways.de/110548716763292872]

Fields for contacts can be configured, multiple addresses, phone numbers, emails, urls and social media / messanger ids can be defined. 

If another address is defined, a new tab panel appears in the contact dialogue.

# EMAIL

## Insert images into mail body in compose dialog

see [https://mastodon.social/@CorneliusWeiss@metaways.de/110740005713241983]

Images can be added to the mail body and the size of the image can be adjusted.

## Improved filter performance by using fulltext filters

## Save custom SIEVE-Script snippets

## Configure send message save location

## Allow mail tagging with custom / tine tags

# FILEMANAGER

## Download folder contents as ZIP archive

## Use of favorites in file-pickers

# SALES

## Reverse-Charge as VAT procedure

see [https://mastodon.social/@CorneliusWeiss@metaways.de/110611594774926313]

Reverse-charge can be defined for invoices - it's possible to define this for each customer or overwrite the customer
 setting for individual invoices. 

## Price type

Configurable: gross or net prices of products

## Send (purchase) invoices to DATEV

# TASKS

## Source field for tasks of leads, projects, ...

see [https://mastodon.social/@CorneliusWeiss@metaways.de/110745392287402212]

The integration of tasks into the CRM and Projects applications has been improved. By adding the "source" to 
tasks, you can now click on the source and the corresponding item (i.e. lead, project, ...) is opened in a separate window.

A new "source"-filter has been added to find the linked tasks and/or sources.

## Collaborators

Add multiple contacts as collaborators to a task for extended filtering, also usable for delegation.

## Dependencies

Tasks can depend on or be dependent on other tasks.

## Import tasks with alarms

New import capabilities that allow the importing of tasks including alarm settings.

# HUMAN RESOURCES

## Bank Holiday Calendar

# TINETRACKER MOBILE APP

We created a new mobile app for creating project + working time sheets for
HR/Timetracker from mobile devices (Android + iOS)
