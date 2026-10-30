<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AnalystArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AnalystRosterController extends Controller
{
    public function index(AnalystArchiveService $archive)
    {
        $analysts = User::where('is_analyst', true)
            ->orderBy('name')
            ->get()
            ->map(fn(User $u) => [
                'id'            => $u->id,
                'name'          => $u->name,
                'display_name'  => $u->analyst_display_name,
                'slug'          => $u->analyst_slug,
                'avatar_url'    => $u->profile_picture_url,
                'record'        => $archive->record($u->id),
            ]);

        return Inertia::render('admin/analysts/Index', [
            'analysts' => $analysts,
        ]);
    }

    public function promote(Request $request, User $user)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:64', 'unique:users,analyst_slug,' . $user->id, 'alpha_dash'],
        ]);

        $user->update([
            'is_analyst'           => true,
            'analyst_slug'         => $validated['slug'],
            'analyst_display_name' => $user->name,
        ]);

        return back()->with('success', "{$user->name} is now an analyst.");
    }

    public function demote(User $user)
    {
        $user->update(['is_analyst' => false]);

        return back()->with('success', "{$user->name} is no longer an analyst. Past picks remain public.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'analyst_display_name' => ['required', 'string', 'max:80'],
            'slug'                 => ['required', 'string', 'max:64', 'unique:users,analyst_slug,' . $user->id, 'alpha_dash'],
            'bio'                  => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'analyst_display_name' => $validated['analyst_display_name'],
            'analyst_slug'         => $validated['slug'],
            'bio'                  => $validated['bio'],
        ]);

        return back()->with('success', 'Analyst profile updated.');
    }
}