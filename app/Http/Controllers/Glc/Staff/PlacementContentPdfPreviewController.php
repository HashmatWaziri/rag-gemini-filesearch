<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Throwable;

final readonly class PlacementContentPdfPreviewController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'max:20480', 'mimetypes:application/pdf'],
        ]);

        try {
            $document = new Parser()->parseContent((string) $request->file('pdf')?->get());
            $text = mb_trim($document->getText());
        } catch (Throwable) {
            return response()->json([
                'message' => 'The PDF could not be parsed. Please check the file or enter the content manually.',
            ], 422);
        }

        if ($text === '') {
            return response()->json([
                'message' => 'No text could be extracted from this PDF. It may be a scanned image.',
            ], 422);
        }

        return response()->json(['text' => $text]);
    }
}
