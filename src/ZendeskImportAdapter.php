<?php

namespace Escalated\Plugins\ImportZendesk;

use Escalated\Laravel\Contracts\ImportAdapter;
use Escalated\Laravel\Models\ImportSourceMap;
use Escalated\Laravel\Support\ExtractResult;

class ZendeskImportAdapter implements ImportAdapter
{
    private array $collectedAttachments = [];
    private ?string $currentJobId = null;

    /** Set by the framework before calling extract() — needed for reply iteration */
    public function setJobId(string $jobId): void
    {
        $this->currentJobId = $jobId;
    }

    public function name(): string
    {
        return 'zendesk';
    }

    public function displayName(): string
    {
        return 'Zendesk';
    }

    public function credentialFields(): array
    {
        return [
            ['name' => 'subdomain', 'label' => 'Subdomain', 'type' => 'text', 'help' => 'e.g., "acme" for acme.zendesk.com'],
            ['name' => 'email', 'label' => 'Admin Email', 'type' => 'text', 'help' => 'Email of an admin user'],
            ['name' => 'token', 'label' => 'API Token', 'type' => 'password', 'help' => 'Generate in Zendesk Admin > API > Tokens'],
        ];
    }

    public function testConnection(array $credentials): bool
    {
        return ZendeskClient::fromCredentials($credentials)->testConnection();
    }

    public function entityTypes(): array
    {
        return ['agents', 'tags', 'custom_fields', 'departments', 'contacts', 'tickets', 'replies', 'attachments'];
    }

    public function defaultFieldMappings(string $entityType): array
    {
        return match ($entityType) {
            'tickets' => [
                'subject' => 'title',
                'status' => 'status',
                'priority' => 'priority',
                'assignee_id' => 'assigned_to',
                'requester_id' => 'requester',
                'group_id' => 'department',
                'tags' => 'tags',
            ],
            default => [],
        };
    }

    public function availableSourceFields(string $entityType, array $credentials): array
    {
        return match ($entityType) {
            'tickets' => [
                ['name' => 'subject', 'label' => 'Subject', 'escalated_options' => ['title']],
                ['name' => 'status', 'label' => 'Status', 'escalated_options' => ['status']],
                ['name' => 'priority', 'label' => 'Priority', 'escalated_options' => ['priority']],
                ['name' => 'assignee_id', 'label' => 'Assignee', 'escalated_options' => ['assigned_to']],
                ['name' => 'requester_id', 'label' => 'Requester', 'escalated_options' => ['requester']],
                ['name' => 'group_id', 'label' => 'Group', 'escalated_options' => ['department']],
                ['name' => 'tags', 'label' => 'Tags', 'escalated_options' => ['tags']],
                ['name' => 'organization_id', 'label' => 'Organization', 'escalated_options' => ['department', 'custom_field', '']],
            ],
            default => [],
        };
    }

    public function extract(string $entityType, array $credentials, ?string $cursor): ExtractResult
    {
        $client = ZendeskClient::fromCredentials($credentials);

        return match ($entityType) {
            'agents' => $this->extractAgents($client, $cursor),
            'tags' => $this->extractTags($client, $cursor),
            'custom_fields' => $this->extractCustomFields($client, $cursor),
            'departments' => $this->extractDepartments($client, $cursor),
            'contacts' => $this->extractContacts($client, $cursor),
            'tickets' => $this->extractTickets($client, $cursor),
            'replies' => $this->extractReplies($client, $cursor),
            'attachments' => $this->extractAttachments($client, $cursor),
            default => new ExtractResult([], null),
        };
    }

    private function extractAgents(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        $data = $cursor
            ? $client->get($cursor)
            : $client->get('users', ['role' => 'agent', 'page[size]' => 100]);

        $records = array_map(
            [ZendeskFieldMapper::class, 'normalizeUser'],
            $data['users'] ?? [],
        );

        $nextCursor = $data['links']['next'] ?? null;

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    private function extractTags(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        $data = $cursor
            ? $client->get($cursor)
            : $client->get('tags', ['page[size]' => 100]);

        $records = array_map(
            fn ($tag) => ZendeskFieldMapper::normalizeTag($tag['name']),
            $data['tags'] ?? [],
        );

        $nextCursor = $data['links']['next'] ?? null;

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    private function extractCustomFields(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        $data = $cursor
            ? $client->get($cursor)
            : $client->get('ticket_fields');

        $records = [];
        foreach ($data['ticket_fields'] ?? [] as $field) {
            // Skip system fields (subject, description, status, etc.)
            if ($field['type'] === 'subject' || $field['type'] === 'description'
                || $field['type'] === 'status' || $field['type'] === 'priority'
                || $field['type'] === 'group' || $field['type'] === 'assignee') {
                continue;
            }

            $typeMap = [
                'text' => 'text', 'textarea' => 'textarea', 'integer' => 'number',
                'decimal' => 'number', 'checkbox' => 'checkbox', 'date' => 'date',
                'tagger' => 'select', 'multiselect' => 'multiselect', 'regexp' => 'text',
            ];

            $records[] = [
                'source_id' => (string) $field['id'],
                'name' => $field['title'] ?? $field['raw_title'] ?? 'Unknown',
                'type' => $typeMap[$field['type']] ?? 'text',
                'options' => array_map(
                    fn ($o) => $o['name'] ?? $o['value'] ?? '',
                    $field['custom_field_options'] ?? [],
                ),
            ];
        }

        $nextCursor = $data['links']['next'] ?? null;

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    private function extractDepartments(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        $data = $cursor
            ? $client->get($cursor)
            : $client->get('groups', ['page[size]' => 100]);

        $records = array_map(
            [ZendeskFieldMapper::class, 'normalizeGroup'],
            $data['groups'] ?? [],
        );

        $nextCursor = $data['links']['next'] ?? null;

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    private function extractContacts(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        // Use incremental export for users (handles large datasets)
        $data = $client->incrementalExport('users', $cursor);

        $records = [];
        foreach ($data['users'] ?? [] as $user) {
            if (($user['role'] ?? '') === 'end-user') {
                $records[] = ZendeskFieldMapper::normalizeUser($user);
            }
        }

        $nextCursor = ($data['end_of_stream'] ?? false) ? null : ($data['after_cursor'] ?? $data['after_url'] ?? null);

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    private function extractTickets(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        $data = $client->incrementalExport('tickets', $cursor);

        $records = array_map(
            [ZendeskFieldMapper::class, 'normalizeTicket'],
            $data['tickets'] ?? [],
        );

        $nextCursor = ($data['end_of_stream'] ?? false) ? null : ($data['after_cursor'] ?? $data['after_url'] ?? null);

        return new ExtractResult($records, $nextCursor, $data['count'] ?? null);
    }

    /**
     * Extract replies by iterating through all imported tickets.
     *
     * Cursor format: "ticket_index:N" where N is the offset into the source map,
     * or "ticket_id:PAGE_URL" for paginating within a single ticket's comments.
     */
    private function extractReplies(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        // Cursor tracks position: either starting fresh, paginating within a ticket,
        // or moving to the next ticket. Format: "idx:N" or "tid:TICKET_ID|PAGE_URL"

        if ($cursor !== null && str_starts_with($cursor, 'tid:')) {
            // Paginating within a ticket's comments
            $rest = substr($cursor, 4);
            $parts = explode('|', $rest, 2);
            $ticketId = $parts[0];
            $pageUrl = $parts[1];

            $data = $client->get($pageUrl);
            $records = $this->normalizeComments($data, $ticketId);

            $nextCursor = isset($data['links']['next'])
                ? "tid:{$ticketId}|{$data['links']['next']}"
                : null; // Done with this ticket — caller will re-enter with next idx

            return new ExtractResult($records, $nextCursor);
        }

        // Get the next ticket to fetch comments for
        $offset = 0;
        if ($cursor !== null && str_starts_with($cursor, 'idx:')) {
            $offset = (int) substr($cursor, 4);
        }

        // Query source maps for the next ticket
        $ticketMap = ImportSourceMap::where('import_job_id', $this->currentJobId ?? '')
            ->where('entity_type', 'tickets')
            ->orderBy('id')
            ->offset($offset)
            ->first();

        if (! $ticketMap) {
            return new ExtractResult([], null); // All tickets processed
        }

        $ticketId = $ticketMap->source_id;

        $data = $client->get("tickets/{$ticketId}/comments", ['page[size]' => 100]);
        $records = $this->normalizeComments($data, $ticketId);

        // Determine next cursor
        if (isset($data['links']['next'])) {
            // More comment pages for this ticket
            $nextCursor = "tid:{$ticketId}|{$data['links']['next']}";
        } else {
            // Move to next ticket
            $nextCursor = "idx:" . ($offset + 1);
        }

        return new ExtractResult($records, $nextCursor);
    }

    private function normalizeComments(array $data, string $ticketId): array
    {
        $records = [];
        foreach ($data['comments'] ?? [] as $comment) {
            $records[] = ZendeskFieldMapper::normalizeComment($comment, $ticketId);

            // Collect attachments from comments and store as attachment records
            foreach ($comment['attachments'] ?? [] as $attachment) {
                $this->collectedAttachments[] = ZendeskFieldMapper::normalizeAttachment(
                    $attachment, 'reply', (string) $comment['id']
                );
            }
        }
        return $records;
    }

    /**
     * Extract attachments collected during reply extraction.
     * Returns all attachment metadata; actual download is handled by the framework.
     */
    private function extractAttachments(ZendeskClient $client, ?string $cursor): ExtractResult
    {
        if ($cursor !== null) {
            return new ExtractResult([], null); // Already returned all in first call
        }

        // Return all attachments collected during reply extraction
        $records = $this->collectedAttachments;
        $this->collectedAttachments = [];

        return new ExtractResult($records, null, count($records));
    }
}
