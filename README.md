<p align="center">
  <a href="https://escalated.dev"><img src="https://raw.githubusercontent.com/escalated-dev/escalated/main/docs/assets/readme/logo.png" width="56" height="56" alt="Escalated"></a>
</p>

<h1 align="center">Zendesk import for Escalated</h1>

<p align="center">Move your Zendesk helpdesk into Escalated, history included.</p>

<p align="center">
  <a href="https://escalated.dev"><b>Website</b></a> ·
  <a href="https://escalated.dev/docs"><b>Docs</b></a> ·
  <a href="https://escalated.dev/cloud"><b>Cloud</b></a> ·
  <a href="https://github.com/escalated-dev/escalated-plugin-import-zendesk/releases"><b>Releases</b></a>
</p>

This plugin imports tickets, contacts, agents, groups, tags, custom fields and full conversation history from Zendesk into Escalated for Laravel, using Zendesk's incremental export API. It's for teams switching from Zendesk, including ones with years of ticket history.

## Features

<table>
<tr>
<td width="33%" valign="top">

**What it imports**
- Agents, contacts, groups, tags, custom fields
- Tickets with status and priority mapping
- Comments: replies and internal notes
- Attachment metadata, downloaded by the framework

</td>
<td width="33%" valign="top">

**Large accounts**
- Incremental cursor export for full history
- Resumable imports with cursor pagination
- Retries on 429 and 5xx, honours Retry-After

</td>
<td width="33%" valign="top">

**Mapping**
- Statuses: new, open, pending, hold, solved, closed
- Priorities: low, normal, high, urgent

</td>
</tr>
</table>

<p align="center"><sub><a href="docs/features.md">Every feature in detail</a></sub></p>

<p align="center">
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"></a>
  <a href="https://getcomposer.org"><img src="https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer"></a>
</p>

## Learn more

- [Every feature](docs/features.md)
- [Configuration, hooks and import order](docs/reference.md)
- [Plugin SDK](https://github.com/escalated-dev/escalated-plugin-sdk): how Escalated plugins are built
- [Escalated docs](https://escalated.dev/docs)
- [Escalated](https://github.com/escalated-dev/escalated), the main repo

## License

MIT. Copyright (c) 2026 Escalated.dev. See [LICENSE](LICENSE).
