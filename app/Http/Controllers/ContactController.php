<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function sendEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        Mail::raw($request->body, function ($message) use ($request) {
            $message->to($request->to)
            ->subject($request->subject);
        });

        return response()->json([
            'message' => 'E-mail enviado com sucesso!',
        ], 200);
    }
}
