<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FollowController extends Controller
{
    /**
     * Follow a user
     */
    public function follow(Request $request)
    {
        $follower = Auth::user();

        $user_id = $request -> user_id;

        if ($follower->id === $user_id) {
            return back()->with('error', 'You cannot follow yourself');
        }

        $user = User::findOrFail($user_id);
        $follower->follow($user);

        return back()->with('success', "You are now following {$user->name}");
    }

    /**
     * Unfollow a user
     */
    public function unfollow(Request $request)
    {
        $follower = Auth::user();

        $user_id = $request -> user_id;
        if ($follower->id === $user_id) {
            return back()->with('error', 'You cannot unfollow yourself');
        }

        $user = User::findOrFail($user_id);
        $follower->unfollow($user);

        return back()->with('success', "You have unfollowed {$user->name}");
    }

    /**
     * Toggle follow status (AJAX)
     */
    public function toggleFollow(Request $request, User $user)
    {
        $follower = Auth::user();

        if ($follower->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow yourself'
            ], 403);
        }

        $isFollowing = $follower->toggleFollow($user);

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
            'followers_count' => $user->getFollowersCount(),
            'message' => $isFollowing 
                ? "You are now following {$user->name}" 
                : "You have unfollowed {$user->name}"
        ]);
    }

    /**
     * Get followers list with pagination
     */
    public function followers(User $user)
    {
        $followers = $user->followers()
            ->withPivot('followed_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'followers' => $followers,
                'total' => $user->getFollowersCount()
            ]
        ]);
    }

    /**
     * Get following list with pagination
     */
    public function following(User $user)
    {
        $following = $user->following()
            ->withPivot('followed_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'following' => $following,
                'total' => $user->getFollowingCount()
            ]
        ]);
    }

    /**
     * Get followed sellers feed (betslips from followed sellers)
     */
    public function feed(Request $request)
    {
        $user = Auth::user();
        $betslips = $user->getFollowedSellersBetslips(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $betslips
            ]);
        }

        return Inertia::render('Feed/Index', [
            'betslips' => $betslips,
            'followed_count' => $user->getFollowingCount(),
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotification(Request $request, User $user)
    {
        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $follower = Auth::user();
        $follower->updateFollowNotifications($user, $request->enabled);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated'
        ]);
    }
}