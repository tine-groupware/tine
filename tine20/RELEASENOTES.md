
TINE RELEASENOTES
=====================
                    
  Release:     Pelle (2024.11)
  Last change: 2024-11-06

# GENERAL CHANGES (Administrative/Operative)

## PHP 8.2 Support

- PHP 8.2 is now fully supported
- PHP 8.0 support has been dropped

## Docker Image

- The docker image is now based on ubuntu noble instead of alpine.

# GENERAL CHANGES (User Interface)

## Modal Windows rework / VueJS
## Application-Dock / Picker
## Multipicker (used for example in "one of" filter and in group functions)
## support colorized number fields
## responsive layout improvements (grid panel)
## Password reveal field

- special note type, is shown in history panel

# ADMIN / OPERATION

## number range configuration (for example for Sales product numbers)
## Send Password via SMS
## Show Mailaccount Sieve-Script
## Tinebase feature "featureShowAccountEmail" has been removed

It is now possible to configure the account "title" display via TWIG templates.
See Tinebase_Config::ACCOUNT_TWIG configuration.

# SSO

## tine can be identity provider

# ADDRESSBOOK

## preferred contact properties (primary mail address, ...)
## GDPR Consent Client / newsletter

# CALENDAR

## New "Floorplan" feature for booking resources (rooms / tables / ...)

see https://tine-docu.s3web.rz1.metaways.net/admins/floorplans/

## series events with individual dates
## Resources-Node in tree
## configurable event types
## sync from remote caldav source (via admin scheduler)

# EMAIL

## Mass-Mailing

- with GDPR-integration & consent-link creation

## expected answer
## Sieve custom scripts

# CRM

## "Copy Lead" action in grid panel

# FILEMANAGER / FILESYSTEM

## mount local or remote WebDAV-folders (flysystem)

i.e. connect multiple tine instances / shared filesystem

## avscan

### notify avscan positive result to admin role

### notes have been removed

- are no longer created + shown and can be removed via CLI Tinebase.removeAllAvScanNotes

# SALES

## Divisions / multi-tenancy
## E-Invoice
## collective billing / "Sammelrechnung"
## reverse billing / "Storno-Rechnungen" workflow improvements
## Reverse-Charge
## gross prices in products and invoices
## Evaluation-Dimensions

# TASKS

## Delegations / Attendee
## Templates
## Subtasks
## Sources (CRM, Projects)
## "TODO" Filter
## create timesheets from tasks

# HUMAN RESOURCES

## yearly turnover

# TIMETRACKER

## working time + turnover (show employee statistics in tbar)

# PHONE & VOIPMANAGER

- Both applications are discontinued and have been removed. Latest versions are in 2023.11 branch / releases.
