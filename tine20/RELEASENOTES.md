
TINE RELEASE NOTES
=====================
                    
  Release:     Mats (2026.11)
  Last change: 2026-11-10

# GENERAL CHANGES (Administrative/Operative)

## IMAP config allowExternalEmail has been refactored to SMTP allowAnyExternalDomains
- imap.allowExternalEmail -> smtp.allowAnyExternalDomains
- smtp.additionaldomains -> smtp.additionalexternaldomains

## PHP Support

- PHP 8.4 Support has been added
- PHP 8.5 Support has been added
- PHP 8.1 Support has been dropped

## Purge Dates per records

## Added logger request id to modlog table

- Useful when using a logger which can be searched by request id

# GENERAL CHANGES (User Interface)

- Face-Lift of the default font, buttons and input fields.
- Filter count UI

# Addressbook

- Added deep links for phone numbers for Click2Dial

# Calendar

- UI: Round corners in Calendar views
- Added event overview export
- CalDAV-Client: improve performance of calendar-query implementation

# Core Data

- Added tine instances (for e-mail icons - but more federation features are coming)
 
# E-Mail

- New icon for all attachments menu
- Added context menu for file locations
- Added new SPAM/HAM remove header strategy

# Event Manager

- Templates for Options and Events
- Registrations have been improved
- Added tab for registrant contact fields
- More Export functionality (Events and Participants with Options)
- Grouping view for options in grid panel

# Inventory

- Added electrical equipment UI

# Matrix Synapse Integrator

- Added preference to disable background starting of chat app
- Added Matrix room management to Addressbook List edit dialog
- Added Addressbook contact custom field "Matrix-ID"

# Poll

- Added new app "Poll" for managing polls and surveys

# Sales

- Multiple quicklook viewers for eDocuments & PDFs (zugpferd)
- UI: document panels in customer/contract

# User Manual

- Removed local docs - have them online
