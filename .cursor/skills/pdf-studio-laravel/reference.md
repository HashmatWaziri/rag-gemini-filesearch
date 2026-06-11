# PDF Studio — Detailed Reference

Extended API reference for features not covered in the main SKILL.md.

## Framework Integrations

**Never expose API keys in client-side code.** Proxy requests through a Laravel controller.

### Laravel Proxy Route (required for all frontend calls)

```php
// routes/api.php
Route::middleware('auth:sanctum')->get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
    return Pdf::view('pdf.invoice')
        ->data(['invoice' => $invoice->load('items')])
        ->download('invoice-' . $invoice->number . '.pdf');
});
```

### Vue 3 — Sync Download

```vue
<!-- components/DownloadInvoice.vue -->
<script setup>
import { ref } from 'vue'
const props = defineProps({ invoiceId: Number })
const loading = ref(false)

async function downloadPdf() {
  loading.value = true
  try {
    const response = await fetch(`/api/invoices/${props.invoiceId}/pdf`, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    })
    if (!response.ok) throw new Error('PDF generation failed')
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `invoice-${props.invoiceId}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <button @click="downloadPdf" :disabled="loading">
    {{ loading ? 'Generating PDF…' : 'Download PDF' }}
  </button>
</template>
```

### React — Sync Download

```jsx
import { useState } from 'react'

export function DownloadPdfButton({ invoiceId }) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  async function handleDownload() {
    setLoading(true)
    setError(null)
    try {
      const response = await fetch(`/api/invoices/${invoiceId}/pdf`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
      })
      if (!response.ok) throw new Error(`Server error: ${response.status}`)
      const blob = await response.blob()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `invoice-${invoiceId}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div>
      <button onClick={handleDownload} disabled={loading}>
        {loading ? 'Generating PDF…' : 'Download PDF'}
      </button>
      {error && <p style={{ color: 'red' }}>{error}</p>}
    </div>
  )
}
```

### Vanilla JavaScript

```html
<button id="downloadBtn">Download Invoice PDF</button>
<p id="status"></p>

<script>
document.getElementById('downloadBtn').addEventListener('click', async () => {
  const btn = document.getElementById('downloadBtn')
  const status = document.getElementById('status')
  btn.disabled = true
  btn.textContent = 'Generating...'
  status.textContent = ''

  try {
    const response = await fetch('/api/invoices/42/pdf', {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    })
    if (!response.ok) throw new Error('Generation failed')
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    Object.assign(document.createElement('a'), { href: url, download: 'invoice-42.pdf' }).click()
    URL.revokeObjectURL(url)
    status.textContent = '✅ Download started'
  } catch (e) {
    status.textContent = '❌ ' + e.message
  } finally {
    btn.disabled = false
    btn.textContent = 'Download Invoice PDF'
  }
})
</script>
```

### Open PDF Inline in New Tab

```javascript
async function previewPdf(invoiceId) {
  const response = await fetch(`/api/invoices/${invoiceId}/pdf`)
  const blob = await response.blob()
  const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
  window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 5000)
}
```

### Node.js — Sync Render (server-to-server)

```javascript
const API_BASE = 'https://yourapp.com/api/pdf-studio'
const API_KEY = process.env.PDF_STUDIO_KEY

async function renderPdf({ view, data = {}, options = {} }) {
  const response = await fetch(`${API_BASE}/render`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json',
      'Accept': 'application/pdf',
    },
    body: JSON.stringify({ view, data, options }),
  })
  if (!response.ok) throw new Error(`PDF render failed (${response.status})`)
  return Buffer.from(await response.arrayBuffer())
}

const pdf = await renderPdf({
  view: 'pdf.invoice',
  data: { invoice: { number: 'INV-001', total: 1500 } },
  options: { format: 'A4' },
})
await fs.writeFile('invoice.pdf', pdf)
```

### Async Render + Polling (Vue/React)

When using SaaS render API or custom async endpoint:

1. POST to `/api/pdf/async` with `{ view, data, output_path, output_disk }` → get `{ id }`
2. Poll GET `/api/pdf/status/{id}` until `status === 'completed'` or `'failed'`
3. On completed: file saved to storage; on failed: check `error` field

```javascript
// Vue composable / React hook pattern
async function startRender(payload) {
  const res = await fetch('/api/pdf/async', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  const { id } = await res.json()
  const interval = setInterval(async () => {
    const poll = await fetch(`/api/pdf/status/${id}`)
    const job = await poll.json()
    if (job.status === 'completed') { clearInterval(interval); /* done */ }
    if (job.status === 'failed') { clearInterval(interval); /* job.error */ }
  }, 2000)
}
```

---

## Table of Contents

```php
Pdf::view('reports.annual')->withTableOfContents(depth: 3, title: 'Contents')->render();
```

- **Auto mode**: All headings included unless `data-toc="false"`
- **Explicit mode**: Only headings with `data-toc` attribute

## Thumbnails

```php
$thumb = Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->thumbnail(width: 300, format: 'png', quality: 85, page: 1);
$thumb->save('thumbnails/inv-001.png');

$thumb = Pdf::thumbnailFromFile('/path/to/document.pdf');
```

Requires `imagick` PHP extension for best results.

## Pro Features (Template Versioning, Workspaces)

Requires `PDF_STUDIO_PRO=true` and migrations.

```php
// Template versioning
$versioning = app(TemplateVersionServiceContract::class);
$version = $versioning->create(definition: $registry->get('invoice'), author: 'Jane', changeNotes: 'Updated layout');
$versions = $versioning->list('invoice');
$definition = $versioning->restore('invoice', versionNumber: 3);
$changes = $versioning->diff('invoice', fromVersion: 2, toVersion: 3);

// Workspaces
$workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => 'admin']);
$access = app(AccessControlContract::class);
$access->canAccess($user->id, $workspace->id);
Route::middleware('pdf-studio.workspace')->group(...);
```

## Visual Builder (JSON Schema)

```php
use PdfStudio\Laravel\Builder\Schema\{DocumentSchema, TextBlock, TableBlock, DataBinding, StyleTokens};

$schema = new DocumentSchema(
    blocks: [
        new TextBlock(content: 'Invoice', tag: 'h1', classes: 'text-2xl font-bold'),
        new TableBlock(headers: ['Item', 'Qty', 'Price'], rowBinding: new DataBinding(variable: 'items', path: 'items'), cellBindings: ['name', 'quantity', 'price']),
    ],
    styleTokens: new StyleTokens(primaryColor: '#1a1a1a', fontFamily: 'Inter, sans-serif'),
);
$html = app(SchemaToHtmlCompiler::class)->compile($schema);
$blade = app(BladeExporter::class)->export($schema);
```

POST `/pdf-studio/builder/preview` with `{"schema": {...}, "format": "html"|"pdf"}`.

## SaaS Tier

Requires `PDF_STUDIO_SAAS=true` and migrations.

- **API Keys**: `ApiKey::generate()` → store hash, show raw key once
- **Sync render**: POST `/api/pdf-studio/render` with Bearer token
- **Async render**: POST `/api/pdf-studio/render/async`, poll GET `/api/pdf-studio/render/{uuid}`
- **Usage metering**: `UsageMeterContract::recordRender()`, `BillableEvent` for billing
- **Analytics**: `AnalyticsServiceContract::getStats()` → total, completed, failed, avg_render_time_ms, total_bytes

## Page Editing

```php
$result = Pdf::reorderPages($pdfContent, [3, 1, 2]);
$result = Pdf::removePages($pdfContent, [2, 4]);
$result = Pdf::rotatePages($pdfContent, 90);
$result = Pdf::rotatePages($pdfContent, 90, [1, 3]);
// File variants: reorderPagesFile, removePagesFile, rotatePagesFile
```

## File Embedding (PDF/A-3)

```php
$result = Pdf::embedFiles($pdfContent, [
    ['path' => '/path/to/invoice.xml', 'name' => 'factur-x.xml', 'mime' => 'text/xml'],
]);
```

## Compose (Multi-Section PDF)

```php
$result = Pdf::compose([
    ['view' => 'pdf.cover', 'data' => ['title' => 'Report'], 'options' => ['landscape' => true]],
    ['view' => 'pdf.financials', 'data' => ['figures' => $figures]],
    ['html' => '<h1>Appendix</h1>'],
]);
```

## Custom Fonts

```php
// AppServiceProvider::boot()
$fonts->register(new FontDefinition(
    family: 'Inter',
    sources: [resource_path('fonts/Inter-Regular.woff2')],
    weight: '400',
    style: 'normal',
));
```

## Asset Resolution

`config/pdf-studio.php`:

```php
'assets' => [
    'inline_local' => true,
    'allow_remote' => true,
    'allowed_hosts' => [],
],
```

## Preview Routes

- GET `/pdf-studio/preview/{template}?format=html`
- GET `/pdf-studio/preview/{template}?format=pdf`

Disabled in production by default (`allowed_environments`).

## Paper Sizes

| Format | Dimensions |
|--------|------------|
| A4 | 210 × 297 mm |
| Letter | 215.9 × 279.4 mm |
| Legal | 215.9 × 355.6 mm |
| A3 | 297 × 420 mm |
| A5 | 148 × 210 mm |

## Margins

```php
Pdf::view('invoices.show')->margins(20)->download('invoice.pdf');
Pdf::view('invoices.show')->margins(top: 15, right: 20, bottom: 30, left: 20)->download('invoice.pdf');
```

## wkhtmltopdf Headers/Footers

```php
Pdf::view('reports.show')
    ->driver('wkhtmltopdf')
    ->headerHtml('<div>Acme Corp — Confidential</div>')
    ->footerHtml('<div>Page [page] of [topage]</div>')
    ->download('report.pdf');
```

## Watermark Options

| Option | Default |
|--------|---------|
| opacity | 0.3 |
| rotation | -45 |
| position | center (top-left, top-right, bottom-left, bottom-right) |
| fontSize | 48 |
| color | #999999 |

## Pdf::fake() Assertions

| Assertion | Description |
|-----------|-------------|
| assertRendered(?Closure) | PDF was rendered |
| assertRenderedView(string) | Specific view |
| assertRenderedCount(int) | Exact count |
| assertDownloaded(string) | Filename |
| assertSavedTo(string, ?disk) | Storage path |
| assertDriverWas(string) | Driver used |
| assertContains(string) | HTML contains |
| assertMerged() | Merge occurred |
| assertMergedCount(int) | Merge sources |
| assertWatermarked() | Watermark applied |
| assertProtected() | Password protection |
| assertNothingRendered() | No renders |

## Dependency Installer Features

| Feature | Package |
|---------|---------|
| Chromium | spatie/browsershot |
| Dompdf | dompdf/dompdf |
| PDF manipulation | setasign/fpdi |
| Form fill & protect | mikehaertl/php-pdftk |
| Barcodes | picqer/php-barcode-generator |
| QR codes | chillerlan/php-qrcode |

## Troubleshooting Code Examples

### Tailwind Classes Missing

```blade
{{-- ❌ Wrong --}}
<div class="text-{{ $color }}-500">...</div>

{{-- ✅ Right: full class map --}}
@php
$colorClass = match($status) {
    'paid'    => 'text-green-600 bg-green-50',
    'overdue' => 'text-red-600 bg-red-50',
    default   => 'text-gray-600 bg-gray-50',
};
@endphp
<div class="{{ $colorClass }}">...</div>
```

### Images Not Rendering

```blade
{{-- ❌ Wrong --}}
<img src="/images/logo.png">

{{-- ✅ Right --}}
<img src="{{ asset('images/logo.png') }}">

{{-- ✅ Best for local: base64 --}}
@php
  $logo = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')));
@endphp
<img src="{{ $logo }}">

{{-- ✅ S3: signed URL --}}
<img src="{{ Storage::temporaryUrl('logos/acme.png', now()->addMinutes(5)) }}">
```

### Fonts Not Loading

```blade
{{-- ❌ Wrong --}}
<link href="https://fonts.googleapis.com/css2?family=Inter">

{{-- ✅ Right: embed via base64 --}}
@php
  $font = base64_encode(file_get_contents(public_path('fonts/Inter-Regular.woff2')));
@endphp
<style>
  @font-face {
    font-family: 'Inter';
    src: url('data:font/woff2;base64,{{ $font }}') format('woff2');
  }
  body { font-family: 'Inter', sans-serif; }
</style>
```

---

## Chromium vs dompdf vs wkhtmltopdf

| Feature | Chromium | dompdf | wkhtmltopdf |
|---------|----------|--------|-------------|
| Flexbox | Full | Partial | Partial |
| CSS Grid | Full | No | No |
| Tailwind v4 | Yes | Utilities only | Utilities only |
| Custom fonts | Yes | Yes | Yes |
| HTML header/footer | No | No | Yes |
