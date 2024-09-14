<?php

namespace Fintech\Reload\Seeders\Canada;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ServiceVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('reload:leather-back-setup');
    }
}
