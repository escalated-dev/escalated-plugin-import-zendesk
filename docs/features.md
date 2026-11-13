# Features

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
