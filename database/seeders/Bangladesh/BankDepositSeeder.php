<?php

namespace Fintech\Reload\Seeders\Bangladesh;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class BankDepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->serviceTypes() as $entry) {
                $serviceTypeChildren = $entry['serviceTypeChildren'] ?? [];

                if (isset($entry['serviceTypeChildren'])) {
                    unset($entry['serviceTypeChildren']);
                }

                $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();

                if ($findServiceTypeModel) {
                    $serviceTypeModel = Business::serviceType()->update($findServiceTypeModel->id, $entry);
                } else {
                    $serviceTypeModel = Business::serviceType()->create($entry);
                }

                if (! empty($serviceTypeChildren)) {
                    array_walk($serviceTypeChildren, function ($item) use (&$serviceTypeModel) {
                        $item['service_type_parent_id'] = $serviceTypeModel->id;
                        Business::serviceType()->create($item);
                    });
                }
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

    private function serviceTypes()
    {
        $image_svg = __DIR__.'/../../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'BRAC BANK LIMITED',
                'service_type_slug' => 'brac_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'brac_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'brac_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'DUTCH-BANGLA BANK LIMITED',
                'service_type_slug' => 'dutch_bangla_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'dutch_bangla_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'dutch_bangla_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'THE CITY BANK LIMITED',
                'service_type_slug' => 'the_city_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'the_city_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'the_city_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'AGRANI BANK LIMITED',
                'service_type_slug' => 'agrani_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'agrani_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'agrani_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'PUBALI BANK LIMITED',
                'service_type_slug' => 'pubali_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pubali_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pubali_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'EASTERN BANK LIMITED',
                'service_type_slug' => 'eastern_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'eastern_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'eastern_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'EXIM BANK LIMITED',
                'service_type_slug' => 'exim_bank_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'exim_bank_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'exim_bank_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id,
                'service_type_name' => 'ISLAMI BANK BANGLDESH LIMITED',
                'service_type_slug' => 'islami_bank_bangladesh_limited',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'islami_bank_bangladesh_limited.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'islami_bank_bangladesh_limited.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true,
            ],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service/logo_png/';

        return [
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'brac_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'BRAC BANK LIMITED', 'service_slug' => 'brac_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'brac_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'brac_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'dutch_bangla_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'DUTCH-BANGLA BANK LIMITED', 'service_slug' => 'dutch_bangla_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'dutch_bangla_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'dutch_bangla_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'the_city_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'THE CITY BANK LIMITED', 'service_slug' => 'the_city_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'the_city_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'the_city_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'agrani_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'AGRANI BANK LIMITED', 'service_slug' => 'agrani_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'agrani_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'agrani_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'pubali_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'PUBALI BANK LIMITED', 'service_slug' => 'pubali_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pubali_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pubali_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'eastern_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'EASTERN BANK LIMITED', 'service_slug' => 'eastern_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'eastern_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'eastern_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'exim_bank_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'EXIM BANK LIMITED', 'service_slug' => 'exim_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'exim_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'exim_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'islami_bank_bangladesh_limited'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'ISLAMI BANK BANGLDESH LIMITED', 'service_slug' => 'islami_bank_bangladesh_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'islami_bank_bangladesh_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'islami_bank_bangladesh_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => config('fintech.business.default_vendor_name', 'Fintech Bangladesh'), 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
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
