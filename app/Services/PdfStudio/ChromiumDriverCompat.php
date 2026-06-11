<?php

declare(strict_types=1);

namespace App\Services\PdfStudio;

use PdfStudio\Laravel\Drivers\ChromiumDriver;
use PdfStudio\Laravel\DTOs\RenderOptions;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

final class ChromiumDriverCompat extends ChromiumDriver
{
    protected function createBrowsershot(string $html, RenderOptions $options): Browsershot
    {
        $browsershot = parent::createBrowsershot($html, $options);

        if (! empty($this->config['no_sandbox'])) {
            $browsershot->noSandbox();
            $browsershot->addChromiumArguments(['disable-setuid-sandbox', 'disable-dev-shm-usage', 'disable-crash-reporter']);
        }

        $projectNodeModules = base_path('node_modules');
        if (is_dir($projectNodeModules)) {
            $browsershot->setNodeModulePath($projectNodeModules);
        }

        $puppeteerHome = storage_path('app/puppeteer-home');
        $puppeteerConfigHome = storage_path('app/puppeteer-home/.config');
        $puppeteerCacheHome = storage_path('app/puppeteer-home/.cache');

        foreach ([$puppeteerHome, $puppeteerConfigHome, $puppeteerCacheHome] as $path) {
            if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
                throw new RuntimeException(sprintf('Unable to create writable Puppeteer runtime path: %s', $path));
            }
        }

        $browsershot->setNodeEnv([
            'HOME' => $puppeteerHome,
            'XDG_CONFIG_HOME' => $puppeteerConfigHome,
            'XDG_CACHE_HOME' => $puppeteerCacheHome,
            'PUPPETEER_CACHE_DIR' => $puppeteerCacheHome,
        ]);

        return $browsershot;
    }
}
