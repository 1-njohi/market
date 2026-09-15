<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Report;

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

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('reports', 'local');
        }

        $report = Report::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'betslip_code' => $validated['betslip_code'] ?? null,
            'contact_email' => $validated['contact_email'],
            'screenshot' => $screenshotPath,
        ]);

        Log::warning('Report submitted', ['report_id' => $report->id, 'type' => $report->type]);

        return back()->with('success', 'Report submitted. We will be in touch.');
    }
}