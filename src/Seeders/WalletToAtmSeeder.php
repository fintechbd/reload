<?php

namespace Fintech\Reload\Seeders;

use Fintech\Reload\Facades\Reload;
use Illuminate\Database\Seeder;

class WalletToAtmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->data();

        foreach (array_chunk($data, 200) as $block) {
            set_time_limit(2100);
            foreach ($block as $entry) {
                Reload::walletToAtm()->create($entry);
            }
        }
    }

    private function data()
    {
        return [];
    }
}
