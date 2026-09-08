<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(PublicSiteDataService $publicSiteData): View
    {
        return view('contact.index', [
            'contact' => $publicSiteData->contactData(),
            'primaryLocation' => $publicSiteData->primaryLocation(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string|min:10',
        ]);

        $recipient = config('mail.from.address');

        Mail::raw(
            "Name: {$data['name']}\nEmail: {$data['email']}\nSubject: {$data['subject']}\n\n{$data['message']}",
            function ($mail) use ($data, $recipient) {
                $mail->to($recipient)
                    ->replyTo($data['email'], $data['name'])
                    ->subject('Contact Form: '.$data['subject']);
            }
        );

        return back()->with('success', true);
    }
}
