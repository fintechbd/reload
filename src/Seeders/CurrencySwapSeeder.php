<?php

namespace Fintech\Reload\Seeders;

use Fintech\Core\Facades\Core;
use Fintech\Reload\Facades\Reload;
use Illuminate\Database\Seeder;

class CurrencySwapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->rootLevelServiceTypeData() as $entry) {
                \Fintech\Business\Facades\Business::serviceType()->create($entry);
            }

            $serviceData = $this->service();

            foreach (array_chunk($serviceData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    \Fintech\Business\Facades\Business::service()->create($entry);
                }
            }

            $serviceStatData = $this->serviceStat();

            foreach (array_chunk($serviceStatData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    \Fintech\Business\Facades\Business::serviceStat()->customStore($entry);
                }
            }
        }

        $data = $this->data();

        foreach (array_chunk($data, 200) as $block) {
            set_time_limit(2100);
            foreach ($block as $entry) {
                Reload::deposit()->create($entry);
            }
        }
    }

    private function data()
    {
        return [];
    }

    private function rootLevelServiceTypeData(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => null,
                'service_type_name' => 'Currency Swap',
                'service_type_slug' => 'currency_swap',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'currency_swap.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'currency_swap.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '1',
                'enabled' => true
            ],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service/logo_png/';

        return [
            [
                'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'currency_swap'])->first()->id,
                'service_vendor_id' => 1,
                'service_name' => 'Currency Swap',
                'service_slug' => 'currency_swap',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'currency_swap.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'currency_swap.png')),
                'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null],
                'enabled' => true
            ],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
        $serviceStats = [];
        $roles = \Fintech\Auth\Facades\Auth::role()->list(['id_not_in_array' => [1]])->pluck('id')->toArray();
        $source_countries = \Fintech\MetaData\Facades\MetaData::country()->list(['is_serving' => true])->pluck('id')->toArray();
        foreach ($serviceLists as $serviceList) {
            $service = \Fintech\Business\Facades\Business::service()->list(['service_slug' => $serviceList['service_slug']])->first();
            $serviceStats[] = [
                'role_id' => $roles,
                'service_id' => $service->getKey(),
                'service_slug' => $service->service_slug,
                'source_country_id' => $source_countries,
                'destination_country_id' => $source_countries,
                'service_vendor_id' => 1,
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

        return $serviceStats;

    }
}
