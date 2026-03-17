<?php

namespace Escalated\Plugins\ImportZendesk;

use Illuminate\Support\Facades\Http;

class ZendeskClient
{
    private string $baseUrl;
    private string $authHeader;

    public function __construct(string $subdomain, string $email, string $token)
    {
        $this->baseUrl = "https://{$subdomain}.zendesk.com/api/v2";
        $this->authHeader = base64_encode("{$email}/token:{$token}");
    }

    public static function fromCredentials(array $credentials): static
    {
        return new static(
            $credentials['subdomain'],
            $credentials['email'],
            $credentials['token'],
        );
    }

    /**
     * Make an authenticated GET request with rate limit handling.
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : "{$this->baseUrl}/{$endpoint}";

        $response = $this->request($url, $query);

        return $response;
    }

    /**
     * Incremental export with cursor-based pagination.
     * Used for tickets, users, organizations.
     */
    public function incrementalExport(string $resource, ?string $cursor = null): array
    {
        if ($cursor) {
            // Cursor is a full URL for incremental exports
            return $this->get($cursor);
        }

        // Start from epoch 0 to get all records
        return $this->get("incremental/{$resource}/cursor", [
            'start_time' => 0,
        ]);
    }

    public function testConnection(): bool
    {
        $response = $this->get('users/me');
        return isset($response['user']['id']);
    }

    private function request(string $url, array $query = [], int $retries = 3): array
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$this->authHeader}",
                'Accept' => 'application/json',
            ])->timeout(30)->get($url, $query);

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                sleep(min($retryAfter, 120));
                continue;
            }

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() >= 500 && $attempt < $retries) {
                sleep(2 ** $attempt);
                continue;
            }

            throw new \RuntimeException(
                "Zendesk API error ({$response->status()}): " . $response->body()
            );
        }

        throw new \RuntimeException('Zendesk API request failed after retries.');
    }
}
