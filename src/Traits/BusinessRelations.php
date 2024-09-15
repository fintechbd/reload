<?php

namespace Fintech\Reload\Traits;

use Fintech\Business\Models\Service;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

if (Core::packageExists('Business')) {
    trait BusinessRelations
    {
        public function service(): BelongsTo
        {
            return $this->belongsTo(config('fintech.business.service_model', Service::class));
        }
    }
} else {
    trait BusinessRelations {}
}
