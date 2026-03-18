# Zendesk Import

Import tickets, contacts, agents, departments, tags, custom fields, and full conversation history from Zendesk into Escalated. The adapter handles all pagination and rate-limit back-off automatically, using Zendesk's incremental export API for large datasets.

## Installation

```bash
# Install via Composer
composer require escalated/escalated-plugin-import-zendesk
```

## Configuration

Credentials are entered through the Escalated import wizard UI. The following fields are required:

| Field | Description |
|---|---|
| `subdomain` | Your Zendesk subdomain (e.g. `acme` for `acme.zendesk.com`) |
| `email` | Email address of an admin user |
| `token` | API token — generate in **Zendesk Admin > Apps & Integrations > APIs > Zendesk API > Tokens** |

## Features

- Imports agents, contacts, departments (groups), tags, and custom ticket fields
- Imports tickets with full status and priority mapping
- Imports all ticket comments (replies and internal notes) with attachment metadata
- Uses Zendesk's incremental cursor export for efficient full-history extraction
- Cursor-based pagination allows resumable imports — safe to restart after failures
- Automatic rate-limit handling: respects `Retry-After` headers and retries on 429/5xx
- Maps Zendesk statuses (`new`, `open`, `pending`, `hold`, `solved`, `closed`) to Escalated equivalents
- Maps Zendesk priorities (`low`, `normal`, `high`, `urgent`) to Escalated equivalents
- Attachment metadata is collected during reply extraction and downloaded by the framework

## Hooks

### Filters

- `import.adapters` — Registers the `ZendeskImportAdapter` with the Escalated import system

## Entity Types Imported

`agents` → `tags` → `custom_fields` → `departments` → `contacts` → `tickets` → `replies` → `attachments`

## Requirements

- Escalated >= 0.6.0
- Zendesk account with API token access enabled
