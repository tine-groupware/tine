
TINE RELEASENOTES
=====================
                    
  Release:     Pelle (2024.11)
  Last change: 2024-10-22

# GENERAL CHANGES (Administrative/Operative)

## PHP 8.2 Support

- PHP 8.2 is now fully supported
- PHP 8.0 support has been dropped

## Docker Image

- The docker image is now based on ubuntu noble instead of alpine.

# GENERAL CHANGES (User Interface)

## Modal Windows rework / VueJS
## Application-Dock / Picker
## Multipicker (i.e. "one of" filter)
## support colorized number fields
## responsive layout improvements (grid panel)
## Password reveal field

eigener notiztyp, wird in historie angezeigt

# ADMIN / OPERATION

## numberable configuration
## Send Password via SMS
## Show Mailaccount Sieve-Script

# SSO

## tine can be identity provider

# ADDRESSBOOK

## preferred contact properties (primary mail address, ...)
## GDPR Consent Client / newsletter

# CALENDAR

## New "Floorplan" feature for booking resources (rooms / tables / ...)
## series events with individual dates
## Resources-Node in tree
## Terminarten
## sync from remote caldav source

via admin scheduler

# EMAIL

## Mass-Mailing

- mit gdrp-integration / consent-link / ...

## expected answer
## Sieve-Customscripts

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
## Sammelrechnungen
## "Storno-Rechnungen" workflow improvements
## Reverse-Charge
## Brutto-Rechnungen
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
