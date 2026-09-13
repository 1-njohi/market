<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bug,seller,buyer,payment,security,other',
            'severity' => 'required|in:low,medium,high,critical',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:20|max:5000',
            'betslip_code' => 'nullable|string|max:32',
            'contact_email' => 'required|email|max:255',
            'screenshot' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:5120',
        ]);

        // Store the screenshot if provided
        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')
                ->store('reports', 'local');
        }

        // Log the report (replace with DB insert + mail later)
        Log::warning('User report submitted', [
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'subject' => $validated['subject'],
            'betslip_code' => $validated['betslip_code'] ?? null,
            'contact_email' => $validated['contact_email'],
            'user_id' => auth()->id(),
            'screenshot' => $screenshotPath,
        ]);

        // TODO: Persist to a reports table and email support@ + security@ for critical items
        // $report = Report::create([...$validated, 'user_id' => auth()->id(), 'screenshot' => $screenshotPath]);
        // Mail::to('support@betslip-pirates.com')->send(new ReportSubmitted($report));

        return back()->with('success', 'Report submitted. We will be in touch.');
    }
}