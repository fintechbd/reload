<?php

namespace Fintech\Reload\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Business\Traits\ServiceSeeder;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToBankSeeder extends Seeder
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
            foreach ($countries as $country) {
                $serviceStatData = $this->serviceStat([$country], [$countries]);
                foreach (array_chunk($serviceStatData, 200) as $block) {
                    set_time_limit(2100);
                    foreach ($block as $entry) {
                        Business::serviceStat()->customStore($entry);
                    }
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
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'withdraw'])->first()->id,
                'service_type_name' => 'Local Bank Transfer',
                'service_type_slug' => 'local_bank_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'local_bank_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'local_bank_transfer.png')),
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
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'local_bank_transfer'])->first()->id,
                'service_vendor_id' => config('fintech.business.default_vendor', 1),
                'service_name' => 'Local Bank Transfer',
                'service_slug' => 'local_bank_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'local_bank_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'local_bank_transfer.png')),
                'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => null, 'account_number' => null, 'transactional_currency' => 'CAD', 'beneficiary_type_id' => 7, 'operator_short_code' => null],
                'enabled' => true,
            ],
        ];
    }

    private function setupTransactionForm(): void
    {
        Transaction::transactionForm()->create([
            'name' => 'Local Bank Transfer',
            'code' => 'local_bank_transfer',
            'enabled' => true,
            'transaction_form_data' => [],
        ]);
    }
}
