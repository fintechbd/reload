<?php

namespace Fintech\Reload\Traits;

use Fintech\Core\Facades\Core;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

if (Core::packageExists('Business')) {
    trait BusinessRelations
    {
        public function service(): BelongsTo
        {
            return $this->belongsTo(config('fintech.business.service_model', \Fintech\Business\Models\Service::class));
        }
    }
} else {
    trait BusinessRelations
    {
    }
}
