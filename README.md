# Laravel Postman Exporter

🚀 **Automatically sync your Laravel API routes with Postman.**

This package iterates through your Laravel routes (api middleware), generates smart JSON request bodies by inspecting your `FormRequest` classes, and syncs them directly to a Postman Collection using the Postman API.

## Features
- **Auto-Sync**: Creates or updates Postman Collections directly from your CLI.
- **Smart Data**: Inspects `rules()` in your `FormRequest` to generate realistic sample JSON data.
- **Organization**: Automatically organizes routes into folders based on route prefixes.
- **Environment Ready**: Automatically sets up `{{base_url}}` as a Postman variable.

## Requirements
- PHP 8.2+
- Laravel 10.0 or 11.0

## Installation

1. **Install via Composer:**
   ```bash
   composer require nabilaahmed/laravel-postman-exporter
   ```

2. **Publish Configuration:**
   ```bash
   php artisan vendor:publish --provider="NabilaAhmed\PostmanExporter\PostmanExporterServiceProvider"
   ```

3. **Configure .env:**
   Add your Postman credentials to your `.env` file:
   ```env
   POSTMAN_EXPORTER_API_KEY=your_postman_api_key
   POSTMAN_EXPORTER_WORKSPACE_ID=your_workspace_id
   ```

## Usage

Run the command to start the export:

```bash
php artisan export:postman
```

The tool will:
1. Ask for a Collection Name.
2. Search for an existing collection with that name in your workspace.
3. If it exists, it updates it. If not, it creates a new one.
4. Export all routes using the `api` middleware.

## Smart Data Generation
If your controller method uses a `FormRequest`, like this:

```php
public function store(CreateUserRequest $request) { ... }
```

The tool will look at `CreateUserRequest::rules()`:
```php
return [
    'name' => 'required|string',
    'email' => 'required|email|unique:users',
];
```

And generate this in Postman:
```json
{
    "name": "Sample text",
    "email": "user@example.com"
}
```

## License
MIT
