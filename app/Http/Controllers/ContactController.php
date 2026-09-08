<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string|min:10',
        ]);

        // هنا تقدر تزيد Mail::send() باش تجي الرسالة على email ديالك
        // أو تحفظها في database

        return back()->with('success', true);
    }
}
