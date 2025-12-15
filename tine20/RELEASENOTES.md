
TINE RELEASE NOTES
=====================
                    
  Release:     Liva (2025.11)
  Last change: 2025-11-13

# GENERAL CHANGES (Administrative/Operative)

## Added configurable rate limits for all users, IPs and APIs
## Introduced batch jobs 
- for example booking multiple invoices in a row

# GENERAL CHANGES (User Interface)

## Make edit dialogs responsive
## Login dialog rework
## Public Pages have been improved
- generalization
- download pages
- consent pages
- and more
## Edit dialog: tab-panel got horizontal scroll-icons

## Clientside markdown support
- only display, editors do not support md yet
## State edit UI in preferences

# ADMIN / OPERATION

## Setup: if gui can't be rendered, we show an "update tine" button
- only the tine update is executed without the js bootstrap
- see https://github.com/tine-groupware/tine/issues/138
## feature(Admin): twig template ui
## Scheduler tasks can be edited and disabled
## disable custom fields
## log login failures by client
- each client has its own failure counter

## IMAP config allowExternalEmail has been refactored to SMTP allowAnyExternalDomains
- imap.allowExternalEmail -> smtp.allowAnyExternalDomains
- smtp.additionaldomains -> smtp.additionalexternaldomains

# SSO

## improved external IDP usage
## support for more auth workflows (token, device,...)
- for example support for MS authenticator (azure)

# ADDRESSBOOK

# CALENDAR

## "Interoperability" improvements (CalDAV)
- iMIP-Messages
- CalDAV-Imports

## add weekday filter
## support for monthly series events for 5th weekday
## send emails only to attendee with a certain status (ACCEPTED, DECLINED ...)

# EMAIL

## Extended mass-mailing functionality (see GDPR)
## E-Mail Templating and nicer Bootstrap-Layouts
## (x)oauth2 sasl auth for IMAP/SMTP servers 
## add support for png and gif images to select image
## Add inserting images to signature editor

# CRM

# FILEMANAGER / FILESYSTEM

## Preview with built-in pdf viewer

# GDPR

## manage mailing list subscription / consent by email and consent pages

# SALES

## further improvements with Invoices
- automatic sending process
- and more

# TASKS

# HUMAN RESOURCES

## make working time/project time attendanceRecorder devices configurable  

# TIME TRACKER

## show/edit correlated timesheets (series of multi day timesheets)
## Customizable XLSX export (with twig template)

# EVENT MANAGER

- A new App has been added for managing Events with configurable options and registrations.  

# CREW SCHEDULING

- A new App has been added for managing / scheduling tasks/shifts for a defined set of attendee.   

# MATRIX SYNAPSE INTEGRATOR

- This app has been rewritten and can now be used to manage matrix accounts on a synapse server that are linked to tine users.

## Matrix Corporal
- It is also possible to use [Matrix Corporal](https://github.com/devture/matrix-corporal) to define users (and later: rooms)

## Element client integration
## matrix directory export
- export tine addressbook to matrix server

# PURCHASING

- Added as new app (moved modules from Sales)
- consists of Suppliers & PurchaseInvoices

# SAAS INSTANCE

- A new App has been added for managing tine SaaS-instances with special confirmation dialogs, action logs and more.
