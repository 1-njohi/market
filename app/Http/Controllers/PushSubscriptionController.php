<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint'       => ['required', 'string', 'max:500'],
            'keys.p256dh'    => ['required', 'string', 'max:255'],
            'keys.auth'      => ['required', 'string', 'max:255'],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->noContent();
    }

    public function status(Request $request)
    {
        $count = $request->user()->pushSubscriptions()->count();

        return response()->json([
            'subscribed' => $count > 0,
            'count'      => $count,
        ]);
    }
}