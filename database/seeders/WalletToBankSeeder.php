<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {
            $parent = Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first();
            Business::serviceTypeManager($this->data(), $parent)
                ->hasService()
                ->hasTransactionForm()
                ->enabled()
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'Local Bank Transfer',
            'service_type_slug' => 'local_bank_transfer',
            'logo_svg' => "{$image_svg}local_bank_transfer.svg",
            'logo_png' => "{$image_png}local_bank_transfer.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
