---
name: pdf-studio-laravel
description: Build, render, and manipulate PDFs in Laravel using sarder/pdfstudio (PDF Studio). Use when implementing PDF generation, invoice/report downloads, Blade-to-PDF rendering, driver selection, merge/watermark/protect operations, Livewire/Filament downloads, or troubleshooting PDF layout issues. Covers Chromium, Cloudflare, Gotenberg, WeasyPrint, wkhtmltopdf, dompdf drivers.
---

# PDF Studio for Laravel

Full coverage of [sarder/pdfstudio](https://sarderiftekhar.github.io/pdf-studio/user-guide.html) — design, preview, and generate PDFs from HTML/Blade with Tailwind or Bootstrap.

## MANDATORY: Always Use Chromium Driver

**The Chromium driver MUST always be used** for all PDF generation in this project. It provides full CSS support (Tailwind, Flexbox, Grid), accurate rendering, and consistent output. Never use dompdf, wkhtmltopdf, or other drivers unless explicitly approved.

```php
Pdf::view('your.view')->driver('chromium')->format('A4')->download('file.pdf');
```

## MANDATORY: Letterhead Centering

When using the Itqan letterhead image (`public/uploads/settings/EducationDivisionLetterheadersEDU.jpg`), the letterhead **MUST always be centered** horizontally on the page. All other content (title, body, tables, etc.) should be properly repositioned below the letterhead with appropriate spacing.

```html
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ public_path('uploads/settings/EducationDivisionLetterheadersEDU.jpg') }}"
         style="max-width: 100%; height: auto; display: inline-block;" />
</div>
```

## When to Use

- Implementing PDF generation from Blade views
- Choosing or switching PDF drivers (always Chromium for this project)
- Adding download/inline PDF responses in controllers or Livewire
- Merging, watermarking, or password-protecting PDFs
- Using `@barcode`, `@qrcode`, `@pageBreak`, `@keepTogether` in Blade
- Troubleshooting Tailwind classes, images, or fonts in PDFs
- Queue/async rendering for heavy reports

## Installation

```bash
composer require sarder/pdfstudio

php artisan vendor:publish --tag=pdf-studio-config
# → config/pdf-studio.php

# Pro & SaaS features:
php artisan vendor:publish --tag=pdf-studio-migrations
php artisan migrate

# Optional dependencies (interactive or all):
php artisan pdf-studio:install
php artisan pdf-studio:install --all
```

Zero config for basic use — package works with `fake` driver out of the box.

## Quick Start

```php
use PdfStudio\Laravel\Facades\Pdf;

// Download
return Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->download('invoice.pdf');

// Inline in browser
return Pdf::view('reports.quarterly')
    ->data(['report' => $report])
    ->inline('report.pdf');

// Save to Storage
Pdf::view('statements.monthly')
    ->data(['account' => $account])
    ->driver('chromium')
    ->format('A4')
    ->landscape()
    ->save('statements/2024-01.pdf', 's3');

// Raw HTML
$result = Pdf::html('<h1>Hello</h1>')->render();
echo $result->bytes;
echo $result->renderTimeMs;
```

### Fluent API

| Method | Description |
|--------|-------------|
| `->view('blade.path')` | Set Blade view |
| `->html('...')` | Raw HTML |
| `->data([...])` | Pass data to view |
| `->driver('chromium')` | Override driver |
| `->format('A4')` | Paper format |
| `->landscape()` | Landscape orientation |
| `->template('name')` | Registered template |
| `->download('file.pdf')` | Download response |
| `->inline('file.pdf')` | Inline response |
| `->save('path', 'disk')` | Save to Storage |
| `->render()` | Raw `PdfResult` |

## Drivers

| Driver | Package/Binary | CSS | Best For |
|--------|----------------|-----|----------|
| **chromium** | spatie/browsershot | Full | Tailwind, complex layouts |
| **dompdf** | dompdf/dompdf | Basic | Simple layouts, no Node |
| **wkhtmltopdf** | System binary | Good | HTML headers/footers |
| **gotenberg** | Docker | Full | PDF/A, Docker |
| **weasyprint** | Python binary | Good | PDF/A, PDF/UA |
| **cloudflare** | API | Full | Serverless |
| **fake** | Built-in | — | Testing |

```bash
# Install drivers
composer require spatie/browsershot   # Chromium
composer require dompdf/dompdf         # dompdf
php artisan pdf-studio:install        # Interactive
php artisan pdf-studio:install --all  # All deps
php artisan pdf-studio:doctor         # Diagnostics
```

## Blade Directives

```blade
@pageBreak
@pageBreakBefore

@avoidBreak
    <div>Stays together</div>
@endAvoidBreak

@showIf($invoice->isPaid())
    <span>PAID</span>
@endShowIf

@keepTogether
    <table>...</table>
@endKeepTogether

@pageNumber(['format' => 'Page {page} of {total}'])

{{-- Barcode: type, value, [options] — use @@barcode if Blade treats @ as directive --}}
@barcode('CODE128', 'INV-001')
@barcode('EAN13', '5901234123457', ['width' => 3, 'height' => 60])

{{-- QR Code: data, [options] --}}
@qrcode('https://example.com/invoice/123')
@qrcode('Payment: $500', ['size' => 8, 'error_correction' => 'H'])
```

**Barcode types**: CODE128 (C128), CODE39 (C39), EAN13, EAN8, UPCA, UPCE, CODE93, ITF14.

**Config** (`config/pdf-studio.php`): `barcode.default_type`, `default_width`, `default_height`; `qrcode.default_size`, `error_correction` (L|M|Q|H).

**Dependencies**: `picqer/php-barcode-generator`, `chillerlan/php-qrcode`.

## Template Registry

```php
// config/pdf-studio.php
'templates' => [
    'invoice' => [
        'view'            => 'pdf.invoice',
        'default_options' => ['format' => 'A4'],
        'data_provider'   => App\Pdf\InvoiceDataProvider::class,
    ],
],

// Usage
Pdf::template('invoice')->data(['id' => 123])->download('invoice-123.pdf');
```

## Queue / Async

```php
use PdfStudio\Laravel\Jobs\RenderPdfJob;

RenderPdfJob::dispatch(
    view:       'invoices.show',
    data:       ['invoice' => $invoice->toArray()],
    outputPath: 'invoices/inv-001.pdf',
    disk:       's3',
    driver:     'chromium',
);

Pdf::batch([
    ['view' => 'invoices.show', 'data' => $inv1, 'outputPath' => 'inv-1.pdf'],
    ['view' => 'invoices.show', 'data' => $inv2, 'outputPath' => 'inv-2.pdf'],
], driver: 'dompdf', disk: 's3');
```

## CSS Framework

```php
// Per-render Bootstrap
Pdf::view('invoices.show')->bootstrap()->render();

// config: 'css_framework' => 'tailwind' | 'bootstrap' | 'none'
```

## PDF Manipulation

Requires `setasign/fpdi` (merge/watermark), `mikehaertl/php-pdftk` (protect/AcroForm).

### Merge

| Source Type | Example |
|-------------|---------|
| File path | `'/path/to/file.pdf'` |
| PdfResult | `Pdf::html('...')->render()` |
| Storage array | `['path' => '...', 'disk' => 's3', 'pages' => '1-3']` |
| Raw bytes | `file_get_contents('file.pdf')` |

```php
$result = Pdf::merge([
    storage_path('cover.pdf'),
    Pdf::html('<h1>Page 1</h1>')->render(),
    ['path' => 'documents/appendix.pdf', 'disk' => 's3', 'pages' => '1-3,5'],
]);
$result->download('merged.pdf');
```

### Watermark

```php
// Text watermark
Pdf::view('report')
    ->watermark('DRAFT', opacity: 0.3, fontSize: 72, position: 'center')
    ->download('draft.pdf');

// Image watermark
Pdf::view('report')
    ->watermarkImage(storage_path('images/logo.png'), opacity: 0.2, position: 'bottom-right')
    ->download('report.pdf');

// Standalone: watermark existing PDF
$result = Pdf::watermarkPdf(file_get_contents('existing.pdf'))
    ->text('CONFIDENTIAL')
    ->opacity(0.5)
    ->rotation(-30)
    ->apply();
```

**Options**: `opacity` (0.3), `rotation` (-45), `position` (center|top-left|top-right|bottom-left|bottom-right), `fontSize` (48), `color` (#999999).

### Password Protect

```php
Pdf::html('<h1>Secret</h1>')
    ->protect(userPassword: 'user123', ownerPassword: 'admin456')
    ->download('protected.pdf');

Pdf::view('contract')
    ->protect(ownerPassword: 'admin', permissions: ['Printing', 'CopyContents'])
    ->save('contracts/signed.pdf');
```

### AcroForm Fill

```php
// List available fields
$fields = Pdf::acroform(storage_path('forms/application.pdf'))->fields();
// → ['name', 'email', 'date', 'signature']

// Fill and flatten
$result = Pdf::acroform(storage_path('forms/application.pdf'))
    ->fill(['name' => 'John', 'email' => 'john@example.com'])
    ->flatten()
    ->output();
$result->download('application-filled.pdf');
```

## Header/Footer Control

Chromium and wkhtmltopdf only.

| Method | Description |
|--------|-------------|
| `headerExceptFirst()` | Hide header on page 1 |
| `footerExceptLast()` | Hide footer on last page |
| `headerOnPages([2, 3])` | Show header only on listed pages |
| `headerExcludePages([1, 5])` | Hide header on listed pages |
| `footerExcludePages([1])` | Hide footer on listed pages |

```php
Pdf::view('report')
    ->headerExceptFirst()
    ->footerExcludePages([1])
    ->download('report.pdf');
```

## Other Operations

```php
// Render cache (bypass with noCache())
$result = Pdf::html('<h1>Report</h1>')->cache(3600)->render();
$fresh = Pdf::html('<h1>Report</h1>')->cache(3600)->noCache()->render();

// Auto-height (receipts, tickets) — maxHeight in pixels
Pdf::view('receipt')->contentFit()->download('receipt.pdf');
Pdf::view('receipt')->contentFit(maxHeight: 3000)->download('receipt.pdf');

// Split & chunk
$parts = Pdf::split($pdfContent, ['1-3', '4-6']);
$chunks = Pdf::chunk($pdfContent, pagesPerChunk: 5);
$ranges = Pdf::chunkRanges($pdfContent, 5);  // ['1-5', '6-10', '11-13']
$plan = Pdf::chunkPlan($pdfContent, 5);     // Detailed chunk plan

// Inspect
$valid = Pdf::isPdf($content);
$pages = Pdf::pageCount($content);
$info = Pdf::inspectPdf($content);
```

## Tailwind Config

```php
// config/pdf-studio.php
'tailwind' => [
    'binary' => env('TAILWIND_BINARY', base_path('node_modules/.bin/tailwindcss')),
    'config' => base_path('tailwind.config.js'),
],
```

Output is cached. Clear with `php artisan pdf-studio:cache-clear`.

## Layout & Design

### Paper Sizes

| Format | Dimensions       | Use                     |
| --------| ------------------| -------------------------|
| A4     | 210 × 297 mm     | International, invoices |
| Letter | 215.9 × 279.4 mm | North American          |
| Legal  | 215.9 × 355.6 mm | Legal documents         |
| A3     | 297 × 420 mm     | Wide tables             |
| A5     | 148 × 210 mm     | Receipts, vouchers      |

### Margins

```php
Pdf::view('invoices.show')->margins(20)->download('invoice.pdf');
Pdf::view('invoices.show')->margins(top: 15, right: 20, bottom: 30, left: 20)->download('invoice.pdf');
```

### Chromium Page Numbers

```blade
{{-- resources/views/pdf/report.blade.php --}}
<style>
  @media print {
    @page { margin: 20mm 15mm 25mm 15mm; }
    .page-footer {
      position: fixed;
      bottom: -18mm;
      width: 100%;
      text-align: center;
      font-size: 9pt;
      color: #888;
    }
  }
</style>
<body>
  <div class="page-footer">
    @pageNumber(['format' => 'Page {page} of {total}'])
  </div>
  <h1>Annual Report</h1>
  <!-- content -->
</body>
```

### wkhtmltopdf HTML Header/Footer

```php
Pdf::view('reports.show')
    ->driver('wkhtmltopdf')
    ->headerHtml('<div style="font-size:10px;text-align:right;">Acme Corp — Confidential</div>')
    ->footerHtml('<div style="font-size:9px;text-align:center;">Page [page] of [topage]</div>')
    ->download('report.pdf');
```

## Livewire / Filament

```php
// Recommended: livewireDownload() — bypasses Livewire 3 / Filament response interception
public function downloadPdf(): mixed
{
    return Pdf::view('pdf.invoice')
        ->data(['invoice' => $this->invoice])
        ->format('A4')
        ->livewireDownload('invoice-' . $this->invoice->number . '.pdf');
}

// Alternative: manual streamDownload
public function downloadPdf(): mixed
{
    return response()->streamDownload(function () {
        echo Pdf::view('pdf.invoice')
            ->data(['invoice' => $this->invoice])
            ->format('A4')
            ->render()->content;
    }, 'invoice-' . $this->invoice->number . '.pdf');
}

// Base64 for Filament modals / iframe
$base64 = Pdf::view('pdf.invoice')->data(['invoice' => $invoice])->render()->toBase64();
$dataUri = 'data:application/pdf;base64,' . $base64;
```

### Livewire Blade

```blade
<button wire:click="downloadPdf" wire:loading.attr="disabled">
  <span wire:loading.remove>Download PDF</span>
  <span wire:loading>Generating...</span>
</button>
```

### Live Preview in iframe

```php
public function previewHtml(): string
{
    return Pdf::view('pdf.invoice')
        ->data(['clientName' => $this->clientName, 'amount' => $this->amount])
        ->render()->html;
}
```

```blade
<iframe srcdoc="{{ $previewHtml }}" style="width:595px; height:842px; border:1px solid #ccc;"></iframe>
```

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Tailwind classes missing | Use full class map or safelist; avoid `class="text-{{ $color }}-500"` |
| Images not rendering | Use `asset()`, base64, or `Storage::temporaryUrl()` — not `/images/logo.png` |
| Fonts not loading | Embed via base64 `@font-face`; Google Fonts URLs fail in headless |
| Page breaks in flex/grid | Wrap in `display:block`; flex/grid ignores `break-before` |
| `@keepTogether` not holding | Section taller than one page — split or reduce font size |
| Table rows splitting | Add `page-break-inside: avoid` to `tr` |
| Extra blank page | Remove trailing margin: `last:mb-0` |

**Debug**: Return `$result->html` with `Content-Type: text/html` to inspect compiled output.

**Full wrong/right code examples**: See [reference.md](reference.md#troubleshooting-code-examples).

**Performance**: Use `dompdf` for simple layouts; `RenderPdfJob` for heavy; `Pdf::batch()` for bulk.

## Testing

```php
// Fake driver
config(['pdf-studio.default_driver' => 'fake']);

// Pdf::fake() assertions
$fake = Pdf::fake();
$this->get('/invoices/1/pdf');
$fake->assertRendered();
$fake->assertRenderedView('pdf.invoice');
$fake->assertDownloaded('invoice-001.pdf');
$fake->assertSavedTo('invoices/001.pdf');
$fake->assertDriverWas('chromium');
$fake->assertNothingRendered();
```

## Itqan-Specific

- **Package**: `sarder/pdfstudio` (dev-codex/v3-foundations-and-drivers)
- **Config**: `config/pdf-studio.php`
- **Custom drivers**: `App\Pdf\CustomDriverManager` (CloudflareDriverCompat for pdfOptions)
- **Driver paths**: wkhtmltopdf `C:\laragon\bin\wkhtmltopdf.exe`, WeasyPrint `C:\laragon\bin\weasyprint.bat`, pdftk `C:\laragon\bin\pdftk.exe`
- **Test controller**: `app/Http/Controllers/PdfDriverTestController.php` — `/pdf-test/*` routes
- **Letterhead Image**: `public/uploads/settings/EducationDivisionLetterheadersEDU.jpg` — use this for all branded PDF exports with Itqan letterhead

## Artisan Commands

```bash
php artisan pdf-studio:install          # Install deps
php artisan pdf-studio:doctor           # Diagnostics
php artisan pdf-studio:cache-clear      # Clear CSS cache
php artisan pdf-studio:cache-clear --render  # Clear render cache
php artisan pdf-studio:templates       # List registered templates
```

## Preview Routes

For rapid development. Disabled in production by default.

- GET `/pdf-studio/preview/{template}?format=html`
- GET `/pdf-studio/preview/{template}?format=pdf`
- POST `/pdf-studio/builder/preview` (JSON schema)

Config: `preview.enabled`, `preview.allowed_environments` (local, staging, testing).

## Reference

- **User guide**: https://sarderiftekhar.github.io/pdf-studio/user-guide.html
- **Detailed API**: See [reference.md](reference.md) for Pro/SaaS, Visual Builder, Table of Contents, Thumbnails, Compose, Custom Fonts, Asset Resolution, Vue/React/Node/Vanilla JS integrations, and full framework examples.
