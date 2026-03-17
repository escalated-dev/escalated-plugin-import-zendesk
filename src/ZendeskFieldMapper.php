<?php

namespace Escalated\Plugins\ImportZendesk;

class ZendeskFieldMapper
{
    public static function statusMap(): array
    {
        return [
            'new' => 'open',
            'open' => 'in_progress',
            'pending' => 'waiting_on_customer',
            'hold' => 'waiting_on_agent',
            'solved' => 'resolved',
            'closed' => 'closed',
        ];
    }

    public static function priorityMap(): array
    {
        return [
            'low' => 'low',
            'normal' => 'medium',
            'high' => 'high',
            'urgent' => 'urgent',
        ];
    }

    public static function mapStatus(?string $zendeskStatus): string
    {
        return static::statusMap()[$zendeskStatus ?? 'new'] ?? 'open';
    }

    public static function mapPriority(?string $zendeskPriority): string
    {
        return static::priorityMap()[$zendeskPriority ?? 'normal'] ?? 'medium';
    }

    /**
     * Normalize a Zendesk ticket into the standard import format.
     */
    public static function normalizeTicket(array $zdTicket): array
    {
        return [
            'source_id' => (string) $zdTicket['id'],
            'title' => $zdTicket['subject'] ?? 'No subject',
            'status' => static::mapStatus($zdTicket['status'] ?? null),
            'priority' => static::mapPriority($zdTicket['priority'] ?? null),
            'requester_source_id' => (string) ($zdTicket['requester_id'] ?? ''),
            'assignee_source_id' => (string) ($zdTicket['assignee_id'] ?? ''),
            'department_source_id' => (string) ($zdTicket['group_id'] ?? ''),
            'tag_source_ids' => $zdTicket['tags'] ?? [],
            'metadata' => [
                'zendesk_id' => $zdTicket['id'],
                'zendesk_url' => $zdTicket['url'] ?? null,
            ],
            'created_at' => $zdTicket['created_at'] ?? null,
            'updated_at' => $zdTicket['updated_at'] ?? null,
        ];
    }

    public static function normalizeUser(array $zdUser): array
    {
        return [
            'source_id' => (string) $zdUser['id'],
            'name' => $zdUser['name'] ?? '',
            'email' => $zdUser['email'] ?? '',
            'role' => $zdUser['role'] ?? 'end-user',
        ];
    }

    public static function normalizeComment(array $zdComment, string $ticketSourceId): array
    {
        return [
            'source_id' => (string) $zdComment['id'],
            'ticket_source_id' => $ticketSourceId,
            'body' => $zdComment['html_body'] ?? $zdComment['body'] ?? '',
            'is_internal_note' => ! ($zdComment['public'] ?? true),
            'author_source_id' => (string) ($zdComment['author_id'] ?? ''),
            'created_at' => $zdComment['created_at'] ?? null,
            'updated_at' => $zdComment['created_at'] ?? null,
        ];
    }

    public static function normalizeGroup(array $zdGroup): array
    {
        return [
            'source_id' => (string) $zdGroup['id'],
            'name' => $zdGroup['name'] ?? 'Unknown',
        ];
    }

    public static function normalizeTag(string $tagName): array
    {
        return [
            'source_id' => $tagName,
            'name' => $tagName,
        ];
    }

    public static function normalizeAttachment(array $zdAttachment, string $parentType, string $parentSourceId): array
    {
        return [
            'source_id' => (string) $zdAttachment['id'],
            'parent_type' => $parentType,
            'parent_source_id' => $parentSourceId,
            'filename' => $zdAttachment['file_name'] ?? 'unknown',
            'mime_type' => $zdAttachment['content_type'] ?? 'application/octet-stream',
            'size' => $zdAttachment['size'] ?? 0,
            'download_url' => $zdAttachment['content_url'] ?? '',
        ];
    }
}
