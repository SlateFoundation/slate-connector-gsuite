# Slate GSuite Connector

## Requirements:

### GSuite Service Account Credentials

GSuite Service Account must be enabled with Domain Wide Delegation. For more information about setting that up, follow these [resources](https://developers.google.com/identity/protocols/oauth2/service-account).

### APIs Enabled

This Connector currently requires the use of two APIs, but more may be added.
- Admin API
    - Sync Users
- Calendar API
    - Create Events

### ClientID Whitelisted

Visit the [Admin Console](http://admin.google.com) with a GSuite admin account. Visit Security &rarr; Advanced Settings &rarr; Authentication: Manage API client access

You'll need to add the scopes (APIs) being used with the required permissions.
i.e. https://www.googleapis.com/auth/calendar for the Calendar API
