# Calendar CLI

This document describes the usage of the tine Groupware CLI (`tine20.php`).

## exportVCalendar

### Description
Exports all events of a calendar in ICS format.

### Usage Example
php tine20.php --username "tine20admin" --password "tine20password" \
--method Calendar.exportVCalendar -- container_id=CALID filename=/my/export/file.ics

## Calendar.import

### Description
Import all events of a calendar in ICS format.

### Usage Example
php tine20.php --username "tine20admin" --password "tine20password" \
--method=Calendar.import plugin=Calendar_Import_Ical importContainerId=123   calendarWithID123Events.ics
