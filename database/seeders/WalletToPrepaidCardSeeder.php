<?php

namespace Fintech\Reload\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToPrepaidCardSeeder extends Seeder
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
//                ->servingPairs([39, 39], [231, 231], [19, 19])
                ->serviceSettings([
                    'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'),
                    'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT)
                ])
                ->enabled()
                ->execute();
        }

        if (Core::packageExists('Transaction') && !Transaction::transactionForm()->list(['code' => 'wallet_to_prepaid_card'])->first()) {
            Transaction::transactionForm()->create([
                'name' => 'Wallet To Prepaid Card',
                'code' => 'wallet_to_prepaid_card',
                'enabled' => true,
                'transaction_form_data' => [],
            ]);
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'Wallet To Prepaid Card',
            'service_type_slug' => 'wallet_to_prepaid_card',
            'logo_svg' => "{$image_svg}wallet_to_prepaid_card.svg",
            'logo_png' => "{$image_png}wallet_to_prepaid_card.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
