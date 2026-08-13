<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RestaurantDocumentController extends Controller
{
    public function show(RestaurantDocument $document): StreamedResponse|RedirectResponse
    {
        if ($document->isRemote()) {
            return redirect()->away($document->publicUrl());
        }

        $path = $document->localPath();

        abort_unless($path, 404, 'Document introuvable sur le disque.');

        $filename = $document->original_name ?: basename($path);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
