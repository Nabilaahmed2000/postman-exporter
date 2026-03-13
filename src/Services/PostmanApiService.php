<?php

namespace NabilaAhmed\PostmanExporter\Services;

use Illuminate\Support\Facades\Http;

class PostmanApiService
{
    protected string $baseUrl = 'https://api.getpostman.com';

    public function findCollection(string $name, string $workspaceId): ?string
    {
        $apiKey = config('postman-exporter.api_key');
        
        $response = Http::withHeaders(['X-Api-Key' => $apiKey])
            ->get("{$this->baseUrl}/collections", [
                'workspace' => $workspaceId
            ]);

        if (!$response->successful()) {
            return null;
        }

        $collections = $response->json('collections') ?? [];
        foreach ($collections as $collection) {
            if ($collection['name'] === $name) {
                return $collection['uid'];
            }
        }

        return null;
    }

    public function syncCollection(string $name, string $workspaceId, array $items, ?string $uid = null): bool
    {
        $apiKey = config('postman-exporter.api_key');
        $appUrl = config('app.url', 'http://localhost');

        $payload = [
            'collection' => [
                'info' => [
                    'name' => $name,
                    'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                    'description' => 'Automatically generated collection from Laravel Routes.'
                ],
                'item' => $items,
                'variable' => [
                    [
                        'key' => 'base_url',
                        'value' => rtrim($appUrl, '/'),
                        'type' => 'string'
                    ],
                    [
                        'key' => 'bearer_token',
                        'value' => 'paste_token_here',
                        'type' => 'string'
                    ]
                ]
            ]
        ];

        $client = Http::withHeaders(['X-Api-Key' => $apiKey]);

        if ($uid) {
            // Update existing
            $response = $client->put("{$this->baseUrl}/collections/{$uid}", $payload);
        } else {
            // Create new
            $response = $client->post("{$this->baseUrl}/collections?workspace={$workspaceId}", $payload);
        }

        return $response->successful();
    }
}
