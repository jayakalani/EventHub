<?php

namespace App\Http\Controllers;

use App\Mail\HelpContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('help');
    }

    public function contact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        Mail::send(new HelpContactMail(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            comment: $validated['comment'],
        ));

        return redirect()
            ->route('help')
            ->withFragment('help-contact')
            ->with('status', 'help-contact-sent');
    }
}
