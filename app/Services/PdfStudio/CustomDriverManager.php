<?php

declare(strict_types=1);

namespace App\Services\PdfStudio;

use PdfStudio\Laravel\Drivers\ChromiumDriver;
use PdfStudio\Laravel\Drivers\DriverManager;

final class CustomDriverManager extends DriverManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function createChromiumDriver(array $config = []): ChromiumDriver
    {
        return new ChromiumDriverCompat($config);
    }
}
