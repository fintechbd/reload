<?php

namespace Fintech\Reload\Traits;

use Fintech\Core\Facades\Core;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

if (Core::packageExists('Auth')) {
    trait AuthRelations
    {
        public function senderReceiver(): BelongsTo
        {
            return $this->belongsTo(
                config('fintech.auth.user_model', \Fintech\Auth\Models\User::class),
                'sender_receiver_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(
                config('fintech.auth.user_model', \Fintech\Auth\Models\User::class),
                'user_id');
        }
    }
} else {
    trait AuthRelations
    {
    }
}
