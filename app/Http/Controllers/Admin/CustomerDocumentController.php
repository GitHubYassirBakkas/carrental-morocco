<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CustomerDocumentController extends Controller
{
    private const DOCUMENTS = [
        'driving-license-front' => ['field' => 'driving_license_front_path', 'name' => 'driving-license-front'],
        'driving-license-back' => ['field' => 'driving_license_back_path', 'name' => 'driving-license-back'],
        'identity-front' => ['field' => 'identity_front_path', 'name' => 'identity-front'],
        'identity-back' => ['field' => 'identity_back_path', 'name' => 'identity-back'],
    ];

    public function show(CustomerProfile $customerProfile, string $document): Response
    {
        abort_unless(array_key_exists($document, self::DOCUMENTS), 404);

        $definition = self::DOCUMENTS[$document];
        $path = $customerProfile->{$definition['field']};

        abort_if(blank($path) || $this->containsTraversal($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $this->safeFilename($definition['name'], $path), [
            'Content-Disposition' => 'inline; filename="'.$this->safeFilename($definition['name'], $path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
