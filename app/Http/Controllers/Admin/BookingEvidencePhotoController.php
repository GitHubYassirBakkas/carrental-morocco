<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingDamage;
use App\Models\BookingPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookingEvidencePhotoController extends Controller
{
    public function inspectionPhoto(BookingPhoto $photo): Response
    {
        abort_unless($photo->inspection()->exists(), 404);

        return $this->serveEvidencePath($photo->path, 'inspections', 'inspection-photo-'.$photo->id);
    }

    public function damagePhoto(BookingDamage $damage, int $photoIndex): Response
    {
        $photos = $damage->photos ?? [];

        abort_unless(array_key_exists($photoIndex, $photos), 404);

        return $this->serveEvidencePath($photos[$photoIndex], 'damages', 'damage-photo-'.$damage->id.'-'.$photoIndex);
    }

    private function serveEvidencePath(mixed $path, string $category, string $filename): Response
    {
        abort_unless(is_string($path), 404);

        $path = $this->normalizePath($path);

        abort_if($path === null, 404);

        $privatePrefix = "booking-evidence/{$category}/";
        $legacyPublicPrefix = "{$category}/";

        if (str_starts_with($path, $privatePrefix) && Storage::disk('local')->exists($path)) {
            return $this->storageResponse('local', $path, $filename);
        }

        if (str_starts_with($path, $legacyPublicPrefix) && Storage::disk('public')->exists($path)) {
            return $this->storageResponse('public', $path, $filename);
        }

        abort(404);
    }

    private function storageResponse(string $disk, string $path, string $filename): Response
    {
        $safeFilename = $this->safeFilename($filename, $path);

        return Storage::disk($disk)->response($path, $safeFilename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalizePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?storage/app/public/#', '', $path);
        $path = preg_replace('#^/?public/storage/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim((string) $path, '/');

        if ($path === '' || $this->containsTraversal($path)) {
            return null;
        }

        return $path;
    }

    private function containsTraversal(string $path): bool
    {
        return str_contains($path, '..') || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    private function safeFilename(string $filename, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension ? $filename.'.'.strtolower($extension) : $filename;
    }
}
