<?php

namespace App\Filesystem;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Config;

/**
 * League's GoogleCloudStorageAdapter exposes publicUrl() on the Flysystem driver, not getUrl()
 * on the adapter. Laravel's default FilesystemAdapter::url() then throws.
 *
 * Temporary URLs use temporaryUrl() on the adapter; Laravel only checks for getTemporaryUrl().
 */
class GoogleCloudStorageFilesystemAdapter extends FilesystemAdapter
{
    public function url($path)
    {
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }

        if (is_object($this->driver) && method_exists($this->driver, 'publicUrl')) {
            return $this->driver->publicUrl($path);
        }

        return parent::url($path);
    }

    public function providesTemporaryUrls()
    {
        return method_exists($this->adapter, 'temporaryUrl') || parent::providesTemporaryUrls();
    }

    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if (method_exists($this->adapter, 'temporaryUrl') && $expiration instanceof DateTimeInterface) {
            return $this->adapter->temporaryUrl($path, $expiration, new Config($options));
        }

        return parent::temporaryUrl($path, $expiration, $options);
    }
}
