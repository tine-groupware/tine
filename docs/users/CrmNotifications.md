# CRM Lead Notifications

This document describes how and when notifications are sent in the CRM application.

## General Requirements

For any lead notification to be sent, the following conditions must be met:

1. **Global Configuration**: The setting `sendnotification` must be enabled in the CRM configuration.
2. **User Preference**: The user's preference "Send notification emails for your own actions" must NOT be set to "Nobody".
3. **Lead "mute" Field**: If the field is true, creating or updating the lead will not trigger a notification (Can be set in the UI edit dialog via the trigger button).

## When are Notifications Sent?

Notifications are triggered when a lead is:

- **Created**
- **Changed**
- **Deleted**

## Who Receives Notifications?

The list of recipients is determined by several factors:

### 1. Based on Lead Relations
If enabled in the CRM configuration, contacts linked to the lead with the following roles will receive notifications:

- **Responsible**: If `sendnotificationtoresponsible` is enabled (Default: Yes).
- **Customer**: If `sendnotificationtocustomer` is enabled (Default: Yes).
- **Partner**: If `sendnotificationtopartner` is enabled (Default: Yes).

### 2. Fallback: Container Access
If no recipients are found through the relations mentioned above, the system can fall back to container permissions:

- If `sendnotificationtoallaccess` is enabled (Default: Yes), all **users** with **read access** to the folder (container) where the lead is located will receive the notification.

NOTE: only single users defined in the container permissions will receive the notification (no groups and/or roles).

### 3. The Updater

- The person who performed the action (created, changed, or deleted the lead) is generally included as a recipient.
- **Exception**: If the updater has set their personal preference "Send notification emails for your own actions" to "All without me", they will be excluded from the recipient list for their own actions.

## Notification Content

Lead notifications include:

- A summary of the action performed (Created/Changed/Deleted).
- Details of the lead (Name, State, Role, Source, Start/End dates, Turnover, Probability, etc.).
- For updates: A list of changed fields showing the old and new values.
- A PDF export of the lead attached to the email.
