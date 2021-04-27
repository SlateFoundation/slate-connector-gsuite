# Slate GSuite Connector

Provides for data synchronization between a Slate site and a Google Workspace / G Suite domain

## Authentication

This module uses **Domain-Wide Delegation** so that an administrator can connect Slate with Google once upfront and enable Slate to carry out actions as any user in the domain without individuals needing to manually link their Google accounts.

## Features

- Push Users
    - Create Google accounts for Slate users
    - Discover existing Google accounts and match them to Slate users
- Create Calender Events
    - [Create Section Event](http://forum.slate.is/t/gsuite-section-calendar-events/206) link added to section homepages automates creating a Google Calendar event with all students and teachers for the section added as guests and a Google Meet link attached

## Setup

1. Follow the first two sections of the Google guide to [Perform Google Workspace Domain-Wide Delegation of Authority](https://developers.google.com/admin-sdk/directory/v1/guides/delegation)
    - [Create the service account and credentials](https://developers.google.com/admin-sdk/directory/v1/guides/delegation#create_the_service_account_and_credentials)
        - Create a new project if you don't have one anyway. The name doesn't matter, you can use the school's name.
        - When creating a key, choose the **JSON** format as the guide recommends
    - [Delegate domain-wide authority to your service account](https://developers.google.com/admin-sdk/directory/v1/guides/delegation#delegate_domain-wide_authority_to_your_service_account)
        - When delegating domain-wide authority to the service account, paste the following list of **OAuth Scopes**:

            ```
            https://www.googleapis.com/auth/admin.directory.user,https://www.googleapis.com/auth/admin.directory.user.security,https://www.googleapis.com/auth/calendar
            ```

    - Ignore the *Instantiate an Admin SDK Directory service object* section

2. Open the downloaded **JSON** file from creating the key, and populate the `API::$clientId`, `API::$clientEmail`, and `API::$privateKey` settings in `php-config/Slate/Connectors/GSuite/API.config.d/credentials.php`
    - Configure `API::$domain` with the primary domain name associated with the Google Workspace

3. Enable the following APIs within the project created at the beginning of step 1:
    - [Admin SDK API](https://console.cloud.google.com/apis/library/admin.googleapis.com)
    - [Google Calendar API](https://console.cloud.google.com/apis/library/calendar-json.googleapis.com)

4. If Slate's original Google Apps connector has been in use, be sure to execute the `Slate/Connectors/GSUite/20210427_connector-mappings.php` migration before using the connector

## Usage

### Connector

Access the connector at <https://myschool.example.org/connectors/gsuite>

### Calendar Event Creator

Access the **Create Section Event** button from the sidebar on any section's homepage.
