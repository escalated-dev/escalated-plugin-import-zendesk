# Reference

Imports tickets, contacts, agents, departments, tags, custom fields, and full conversation history from Zendesk into Escalated. Uses Zendesk's incremental export API for efficient large-dataset extraction.

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
