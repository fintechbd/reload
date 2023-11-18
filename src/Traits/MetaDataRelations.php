<?php

namespace Fintech\Reload\Traits;

use Fintech\Core\Facades\Core;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

if (Core::packageExists('MetaData')) {
    trait MetaDataRelations
    {
        public function sourceCountry(): BelongsTo
        {
            return $this->belongsTo(
                config('fintech.metadata.country_model', \Fintech\MetaData\Models\Country::class),
                'source_country_id');
        }

        public function destinationCountry(): BelongsTo
        {
            return $this->belongsTo(
                config('fintech.metadata.country_model', \Fintech\MetaData\Models\Country::class),
                'destination_country_id');
        }
    }
} else {
    trait MetaDataRelations
    {
    }
}
