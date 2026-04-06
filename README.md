# Escalated Plugin: Import Zendesk

Imports tickets, contacts, agents, departments, tags, custom fields, and full conversation history from Zendesk into Escalated. Uses Zendesk's incremental export API for efficient large-dataset extraction.

## Features

- Imports agents, contacts, departments (groups), tags, and custom ticket fields
- Imports tickets with full status and priority mapping
- Imports all ticket comments (replies and internal notes) with attachment metadata
- Uses Zendesk's incremental cursor export for efficient full-history extraction
- Cursor-based pagination for resumable imports
- Automatic rate-limit handling with `Retry-After` header support and retry on 429/5xx
- Maps Zendesk statuses (new, open, pending, hold, solved, closed) to Escalated equivalents
- Maps Zendesk priorities (low, normal, high, urgent) to Escalated equivalents
- Attachment metadata collected during reply extraction and downloaded by the framework

## Configuration

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `subdomain` | text | Yes | Your Zendesk subdomain, e.g. `acme` for `acme.zendesk.com`. |
| `email` | text | Yes | Email address of an admin user. |
| `token` | password | Yes | API token from Zendesk Admin > Apps & Integrations > APIs > Zendesk API > Tokens. |

## Hooks

### Filters
- `import.adapters` — Registers the Zendesk import adapter with the Escalated import system.

## Entity Import Order

`agents` > `tags` > `custom_fields` > `departments` > `contacts` > `tickets` > `replies` > `attachments`

## Installation

```bash
npm install @escalated-dev/plugin-import-zendesk
```

## License

MIT
