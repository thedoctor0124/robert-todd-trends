<?php

namespace App\Providers;

use App\Filesystem\GoogleCloudStorageFilesystemAdapter;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Visibility;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https') || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Storage::extend('gcs', function ($app, array $config) {
            $clientOptions = ['projectId' => $config['project_id']];
            if (! empty($config['key_file'])) {
                $clientOptions['keyFilePath'] = $config['key_file'];
            }
            $client = new StorageClient($clientOptions);
            $bucket = $client->bucket($config['bucket']);
            $defaultVisibility = ($config['visibility'] ?? 'private') === 'public'
                ? Visibility::PUBLIC
                : Visibility::PRIVATE;
            $adapter = new GoogleCloudStorageAdapter(
                $bucket,
                $config['path_prefix'] ?? '',
                null,
                $defaultVisibility,
            );
            $flysystem = new Flysystem($adapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ]));

            return new GoogleCloudStorageFilesystemAdapter($flysystem, $adapter, $config);
        });
    }
}
