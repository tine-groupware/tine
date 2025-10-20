
TINE RELEASE NOTES
=====================
                    
  Release:     Liva (2025.11)
  Last change: 2025-10-20

# GENERAL CHANGES (Administrative/Operative)

## add configurable rate limits for all users, IPs and APIs

# GENERAL CHANGES (User Interface)

## make edit dialogs responsive
## Login dialog rework
## Public Pages have been improved
- generalization
- download pages
- consent pages
- and more

## clientside markdown support

# ADMIN / OPERATION

## feature(Admin): twig template ui
## Scheduler tasks can be edited and disabled
## disable custom fields
## log login failures by client

# SSO

## improved external IDP usage
## support for more auth workflows (token, device,...)
- TODO add more info
- 
# ADDRESSBOOK

# CALENDAR

## "Interoperability" improvements (CalDAV)
- iMIP-Messages
- CalDAV-Imports

## add weekday filter
## support for monthly series events for 5th weekday

# EMAIL

## Extended mass-mailing functionality (see GDPR)
## E-Mail Templating and nicer Bootstrap-Layouts
## xoauth2 sasl auth
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
## Customizable XLSX export (twig template)

# EVENT MANAGER

- A new App has been added for managing Events with configurable options and registrations.  

# CREW SCHEDULING

- A new App has been added for managing / scheduling tasks/shifts for a defined set of attendee.   

# SAAS INSTANCE

- A new App has been added for managing tine SaaS-instances with special confirmation dialogs, job logs and more.

# MATRIX SYNAPSE INTEGRATOR

- This app has been rewritten and can now be used to manage matrix accounts on a synapse server that are linked to tine users.

## Matrix Corporal
- It is also possible to use [Matrix Corporal](https://github.com/devture/matrix-corporal) to define users (and later: rooms)

## Element client integration
## matrix directory export

# PURCHASES

- Added as new app (moved modules from Sales)
