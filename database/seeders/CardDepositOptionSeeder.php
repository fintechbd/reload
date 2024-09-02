<?php

namespace Fintech\Reload\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class CardDepositOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parent = Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first();
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        if ($parent) {
            $entries = [
                [
                    'service_type_parent_id' => $parent->id,
                    'service_type_name' => 'VISA CARD',
                    'service_type_slug' => 'visa_card',
                    'logo_svg' => $image_svg.'visa_card.svg',
                    'logo_png' => $image_png.'visa_card.png',
                    'service_type_is_parent' => 'no',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '3',
                    'enabled' => true,
                    'source_country' => [19, 39, 231],
                    'destination_country' => [19, 39, 231],
                    'roles' => range(2, 7),
                    'service_vendor_id' => 1,
                ],
                [
                    'service_type_parent_id' => $parent->id,
                    'service_type_name' => 'MASTER CARD',
                    'service_type_slug' => 'master_card',
                    'logo_svg' => $image_svg.'master_card.svg',
                    'logo_png' => $image_png.'master_card.png',
                    'service_type_is_parent' => 'no',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '3',
                    'enabled' => true,
                    'source_country' => [19, 39, 231],
                    'destination_country' => [19, 39, 231],
                    'roles' => range(2, 7),
                    'service_vendor_id' => 1,
                ],
                [
                    'service_type_parent_id' => $parent->id,
                    'service_type_name' => 'DISCOVER CARD',
                    'service_type_slug' => 'discover_card',
                    'logo_svg' => $image_svg.'discover_card.svg',
                    'logo_png' => $image_png.'discover_card.png',
                    'service_type_is_parent' => 'no',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '3',
                    'enabled' => true,
                    'source_country' => [19, 39, 231],
                    'destination_country' => [19, 39, 231],
                    'roles' => range(2, 7),
                    'service_vendor_id' => 1,
                ],
            ];
        }

        if (Core::packageExists('Business')) {

            $serviceTypes = $this->serviceTypes();

            if (! empty($serviceTypes)) {

                $parent = Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first();

                foreach ($serviceTypes as $entry) {

                    $entry['service_type_parent_id'] = ($parent) ? $parent->id : null;

                    $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();

                    ($findServiceTypeModel)
                        ? Business::serviceType()->update($findServiceTypeModel->id, $entry)
                        : Business::serviceType()->create($entry);
                }

                $serviceData = $this->service();

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
        }
    }

    private function serviceTypes(): array
    {

        return [
            [
                'service_type_name' => 'VISA CARD',
                'service_type_slug' => 'visa_card',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'visa_card.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'visa_card.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_name' => 'MASTER CARD',
                'service_type_slug' => 'master_card',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'master_card.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'master_card.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_name' => 'DISCOVER CARD',
                'service_type_slug' => 'discover_card',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'discover_card.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'discover_card.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service/logo_png/';

        return [
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'visa_card'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'VISA CARD', 'service_slug' => 'visa_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'visa_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'visa_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'master_card'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'MASTER CARD', 'service_slug' => 'master_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'master_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'master_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'discover_card'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'DISCOVER CARD', 'service_slug' => 'discover_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'discover_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'discover_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
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
                    'service_vendor_id' => 1,
                    'service_stat_data' => [
                        [
                            'lower_limit' => '10.00',
                            'higher_limit' => '5000.00',
                            'local_currency_higher_limit' => '25000.00',
                            'charge' => mt_rand(1, 7).'%',
                            'discount' => mt_rand(1, 7).'%',
                            'commission' => '0',
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
}
