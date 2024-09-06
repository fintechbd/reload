<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class WalletToWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $parent = Business::serviceType()->list(['service_type_slug' => 'withdraw'])->first();

            Business::serviceTypeManager($this->data(), $parent)
                ->hasService()
                ->service(['service_name' => 'Wallet to Wallet Transfer'])
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
            'service_type_name' => 'Wallet to Wallet',
            'service_type_slug' => 'wallet_to_wallet',
            'logo_svg' => "{$image_svg}wallet_to_wallet.svg",
            'logo_png' => "{$image_png}wallet_to_wallet.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
