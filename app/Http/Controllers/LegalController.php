<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteDataService;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(PublicSiteDataService $publicSiteData): View
    {
        return view('legal.privacy', [
            'contact' => $publicSiteData->contactData(),
        ]);
    }

    public function terms(PublicSiteDataService $publicSiteData): View
    {
        return view('legal.terms', [
            'contact' => $publicSiteData->contactData(),
        ]);
    }

    public function notice(PublicSiteDataService $publicSiteData): View
    {
        return view('legal.notice', [
            'contact' => $publicSiteData->contactData(),
            'primaryLocation' => $publicSiteData->primaryLocation(),
        ]);
    }
}
