<?php

namespace Dev\PostmanExporter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Dev\PostmanExporter\Services\PostmanDataGenerator;
use Dev\PostmanExporter\Services\PostmanApiService;
use Illuminate\Support\Str;

class ExportToPostman extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:postman';

    /**
     * The console command description.
     */
    protected $description = 'Export API routes to a Postman Collection via Postman API';

    /**
     * Execute the console command.
     */
    public function handle(PostmanDataGenerator $generator, PostmanApiService $api): int
    {
        $collectionName = $this->ask('Enter Postman Collection Name', 'Laravel API Export');
        $workspaceId = config('postman-exporter.workspace_id');
        $apiKey = config('postman-exporter.api_key');

        if (!$apiKey || !$workspaceId) {
            $this->error('Please configure POSTMAN_EXPORTER_API_KEY and POSTMAN_EXPORTER_WORKSPACE_ID in your .env file.');
            return 1;
        }

        $this->info("🔍 Discovering API routes...");

        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return in_array('api', $route->gatherMiddleware()) || str_starts_with($route->uri(), 'api/');
        });

        if ($routes->isEmpty()) {
            $this->warn('No API routes found.');
            return 0;
        }

        $itemsByFolder = [];

        foreach ($routes as $route) {
            $methods = array_diff($route->methods(), ['HEAD', 'PATCH']); // Simplify methods
            $uri = $route->uri();
            $action = $route->getActionName();

            // Organization Strategy: Use prefix or first segment
            $segments = explode('/', trim($uri, '/'));
            $folderName = $segments[1] ?? ($segments[0] ?? 'General');
            $folderName = Str::title($folderName);

            // Body Generation
            $body = [];
            if (Str::contains($action, '@')) {
                [$controller, $method] = explode('@', $action);
                $body = $generator->generateForRoute($controller, $method);
            }

            foreach ($methods as $method) {
                $itemsByFolder[$folderName][] = [
                    'name' => $route->getName() ?? "[$method] $uri",
                    'request' => [
                        'method' => $method,
                        'header' => [
                            ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
                            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode($body, JSON_PRETTY_PRINT),
                            'options' => ['raw' => ['language' => 'json']]
                        ],
                        'url' => [
                            'raw' => "{{base_url}}/{$uri}",
                            'host' => ["{{base_url}}"],
                            'path' => explode('/', $uri)
                        ],
                        'description' => "Controller Action: $action"
                    ],
                    'response' => []
                ];
            }
        }

        // Transform to Postman Item structure
        $finalItems = [];
        foreach ($itemsByFolder as $folder => $requests) {
            $finalItems[] = [
                'name' => $folder,
                'item' => $requests
            ];
        }

        $this->info("🚀 Syncing with Postman Cloud...");

        $existingUid = $api->findCollection($collectionName, $workspaceId);
        
        $success = $api->syncCollection($collectionName, $workspaceId, $finalItems, $existingUid);

        if ($success) {
            $this->info("✨ Successfully exported " . $routes->count() . " routes to Postman!");
            return 0;
        }

        $this->error("❌ Failed to sync collection. Check your API key and permissions.");
        return 1;
    }
}
