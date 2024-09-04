<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Business\Traits\ServiceSeeder;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToWalletSeeder extends Seeder
{
    use ServiceSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->serviceTypes() as $entry) {
                Business::serviceType()->create($entry);
            }

            $serviceData = $this->services();

            foreach (array_chunk($serviceData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::service()->create($entry);
                }
            }

            $countries = MetaData::country()->list(['is_serving' => true])->pluck('id')->toArray();
            $serviceStatData = $this->serviceStat($countries, $countries);

            foreach (array_chunk($serviceStatData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::serviceStat()->customStore($entry);
                }
            }
        }

        $this->setupTransactionForm();
    }

    /**
     * @return array[]
     */
    private function serviceTypes(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => null,
                'service_type_name' => 'Wallet to Wallet',
                'service_type_slug' => 'wallet_to_wallet',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_to_wallet.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_to_wallet.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '2',
                'enabled' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    private function services(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service/logo_png/';

        return [
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_to_wallet'])->first()->id,
                'service_vendor_id' => config('fintech.business.default_vendor', 1),
                'service_name' => 'Wallet to Wallet Transfer',
                'service_slug' => 'wallet_to_wallet',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_to_wallet.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_to_wallet.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => [
                    'visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'),
                    'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT',
                    'beneficiary_type_id' => null, 'operator_short_code' => null,
                ],
                'enabled' => true,
            ],
        ];
    }

    private function setupTransactionForm(): void
    {
        Transaction::transactionForm()->create([
            'name' => 'Wallet To Wallet Transfer',
            'code' => 'wallet_to_wallet',
            'enabled' => true,
            'transaction_form_data' => [],
        ]);
    }
}
