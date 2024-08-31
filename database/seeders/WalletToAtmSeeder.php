<?php

namespace Fintech\Reload\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToAtmSeeder extends Seeder
{
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

            $serviceStatData = $this->serviceStat();

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
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'withdraw'])->first()->id,
                'service_type_name' => 'Local ATM Transfer',
                'service_type_slug' => 'local_atm_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'local_atm_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'local_atm_transfer.png')),
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
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'local_atm_transfer'])->first()->id,
                'service_vendor_id' => config('fintech.business.default_vendor', 1),
                'service_name' => 'Local ATM Transfer',
                'service_slug' => 'local_atm_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'local_atm_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'local_atm_transfer.png')),
                'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => null, 'account_number' => null, 'transactional_currency' => 'CAD', 'beneficiary_type_id' => 8, 'operator_short_code' => null],
                'enabled' => true,
            ],
        ];
    }

    private function serviceStat(): array
    {
        $serviceLists = $this->services();
        $serviceStats = [];
        $roles = Auth::role()->list(['id_not_in' => [1]])->pluck('id')->toArray();
        $source_countries = MetaData::country()->list(['is_serving' => true])->pluck('id')->toArray();
        if (! empty($roles) && ! empty($source_countries)) {
            foreach ($serviceLists as $serviceList) {
                $service = Business::service()->list(['service_slug' => $serviceList['service_slug']])->first();
                $serviceStats[] = [
                    'role_id' => $roles,
                    'service_id' => $service->getKey(),
                    'service_slug' => $service->service_slug,
                    'source_country_id' => $source_countries,
                    'destination_country_id' => $source_countries,
                    'service_vendor_id' => config('fintech.business.default_vendor', 1),
                    'service_stat_data' => [
                        [
                            'lower_limit' => '10.00',
                            'higher_limit' => '5000.00',
                            'local_currency_higher_limit' => '25000.00',
                            'charge' => '5%',
                            'discount' => '5%',
                            'commission' => '5%',
                            'cost' => '0.00',
                            'charge_refund' => 'yes',
                            'discount_refund' => 'yes',
                            'commission_refund' => 'yes',
                        ],
                    ],
                    'enabled' => true,
                ];
            }
        }

        return $serviceStats;

    }

    private function setupTransactionForm(): void
    {
        Transaction::transactionForm()->create([
            'name' => 'Local ATM Transfer',
            'code' => 'local_atm_transfer',
            'enabled' => true,
            'transaction_form_data' => [],
        ]);
    }
}
