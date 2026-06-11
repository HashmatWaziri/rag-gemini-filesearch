<?php

declare(strict_types=1);

namespace App\Services\PdfStudio;

final class ChromiumExecutableResolver
{
    /**
     * @return list<string>
     */
    public static function candidatePaths(): array
    {
        $paths = [];

        $embedded = array_merge(
            glob(storage_path('app/chrome-for-pdf/chrome-headless-shell/*/chrome-headless-shell-linux64/chrome-headless-shell')) ?: [],
            glob(storage_path('app/chrome-for-pdf/chrome/*/chrome-linux64/chrome')) ?: [],
        );
        sort($embedded);
        foreach (array_reverse($embedded) as $path) {
            $paths[] = $path;
        }

        $cacheRoots = array_values(array_unique(array_filter([
            is_string(getenv('HOME')) && getenv('HOME') !== '' ? getenv('HOME').'/.cache/puppeteer' : null,
            '/var/www/.cache/puppeteer',
        ])));

        foreach ($cacheRoots as $root) {
            $fromCache = array_merge(
                glob($root.'/chrome-headless-shell/*/chrome-headless-shell-linux64/chrome-headless-shell') ?: [],
                glob($root.'/chrome/*/chrome-linux64/chrome') ?: [],
            );
            sort($fromCache);
            foreach (array_reverse($fromCache) as $path) {
                $paths[] = $path;
            }
        }

        $paths[] = '/opt/google/chrome/google-chrome';
        $paths[] = '/usr/bin/google-chrome-stable';
        $paths[] = '/usr/bin/google-chrome';
        $paths[] = '/usr/bin/chromium';
        $paths[] = '/usr/bin/chromium-browser';
        $paths[] = '/snap/bin/chromium';

        if (PHP_OS_FAMILY === 'Darwin') {
            $paths[] = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
        }

        return $paths;
    }

    public static function resolve(?string $configuredBinary): ?string
    {
        if (is_string($configuredBinary) && $configuredBinary !== '') {
            if (is_executable($configuredBinary) && ! self::isSnapShim($configuredBinary)) {
                return $configuredBinary;
            }
        }

        foreach (self::candidatePaths() as $path) {
            if (! is_executable($path)) {
                continue;
            }
            if (self::isSnapShim($path)) {
                continue;
            }

            return $path;
        }

        return null;
    }

    private static function isSnapShim(string $path): bool
    {
        $real = realpath($path);
        if ($real !== false && str_contains($real, '/snap/')) {
            return true;
        }

        $head = @file_get_contents($path, false, null, 0, 8192);
        if (! is_string($head)) {
            return false;
        }

        if (preg_match('/\bsnap\s+run\b/', $head) === 1) {
            return true;
        }

        if (str_contains($head, 'chromium.chromium') && str_contains($head, 'snap')) {
            return true;
        }

        return false;
    }
}
