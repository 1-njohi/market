<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'type', 'severity', 'subject', 'description',
        'betslip_code', 'contact_email', 'screenshot', 'status',
        'resolution_notes', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }

    public function scopeOpen($q) { return $q->whereIn('status', ['open', 'investigating']); }
}