<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Notification `data->type` values grouped by category for the page
     * filter chips. Keep in sync with the notifications' `type` payload
     * keys in app/Notifications/*.
     */
    private const TYPE_GROUPS = [
        'betslip'  => ['betslip_won', 'betslip_lost', 'betslip_voided'],
        'contest'  => ['contest_join_requested', 'contest_entry_status', 'contest_settled'],
        'referral' => ['referral_signup', 'referral_attributed', 'referral_reward'],
        'wallet'   => ['deposit', 'withdrawal', 'pending_release', 'payout', 'fee'],
        'watch'    => ['watch_settled'],
    ];

    private const PER_PAGE = 20;

    /**
     * GET /notifications — the full page (Inertia).
     *
     * Filters (query string, preserved across pagination via
     * withQueryString):
     *   - status = all | unread
     *   - type   = betslip | contest | referral | wallet | watch
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $status = $request->query('status') === 'unread' ? 'unread' : 'all';

        $type = $request->query('type');
        if (!is_string($type) || !array_key_exists($type, self::TYPE_GROUPS)) {
            $type = null;
        }

        $query = $user->notifications()->orderByDesc('created_at');

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if ($type) {
            $query->whereIn('data->type', self::TYPE_GROUPS[$type]);
        }

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();
        $paginator->through(fn ($n) => $this->present($n));

        return Inertia::render('Notifications', [
            'notifications' => $paginator,
            'filters'       => [
                'status' => $status,
                'type'   => $type,
            ],
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * GET /notifications/feed — the bell dropdown (JSON).
     *
     * Capped at 50 (dropdown is not paginated); the page is where the
     * full archive lives.
     */
    public function feed(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->take(50)
            ->get()
            ->map(fn ($n) => $this->present($n));

        return response()->json([
            'success'       => true,
            'unread_count'  => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Normalise a notification row to the payload consumed by both the
     * bell and the full page. Only `id`, `type`, `title`, `message`,
     * `time`, `created_at`, and `read` are guaranteed — every other key
     * is type-specific and may be null.
     */
    private function present($notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id'                 => $notification->id,
            'type'               => $data['type'] ?? 'general',
            'title'              => $data['title'] ?? null,
            'message'            => $data['body'] ?? '',

            // Betslip-linked
            'betslip_id'         => $data['betslip_id'] ?? null,
            'betslip_code'       => $data['betslip_code'] ?? null,
            'outcome'            => $data['outcome'] ?? null,

            // Wallet / referral amounts
            'amount'             => $data['amount'] ?? null,
            'referrer_name'      => $data['referrer_name'] ?? null,
            'referee_name'       => $data['referee_name'] ?? null,

            // Contest-linked
            'contest_id'         => $data['contest_id'] ?? null,
            'contest_uuid'       => $data['contest_uuid'] ?? null,
            'contest_name'       => $data['contest_name'] ?? null,
            'requester_name'     => $data['requester_name'] ?? null,
            'host_name'          => $data['host_name'] ?? null,
            'status'             => $data['status'] ?? null,
            'rank'               => $data['rank'] ?? null,
            'correct'            => $data['correct'] ?? null,
            'units'              => $data['units'] ?? null,
            'total_participants' => $data['total_participants'] ?? null,

            // Meta
            'time'               => $notification->created_at->diffForHumans(),
            'created_at'         => $notification->created_at->toISOString(),
            'read'               => !is_null($notification->read_at),
            'read_at'            => $notification->read_at?->toISOString(),
        ];
    }

    /**
     * POST /notifications/{id}/read
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /notifications/read-all
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * DELETE /notifications/{id}
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $deleted = $user->notifications()->where('id', $id)->delete();

        return response()->json([
            'success'      => (bool) $deleted,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}