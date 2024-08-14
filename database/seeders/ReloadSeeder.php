<?php

namespace Fintech\Reload\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class ReloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $serviceTypes = $this->serviceTypes();

            if (! empty($serviceTypes)) {

                foreach ($this->serviceTypes() as $entry) {
                    $serviceTypeChild = $entry['serviceTypeChild'] ?? [];

                    if (isset($entry['serviceTypeChild'])) {
                        unset($entry['serviceTypeChild']);
                    }

                    $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();
                    if ($findServiceTypeModel) {
                        $serviceTypeModel = Business::serviceType()->update($findServiceTypeModel->id, $entry);
                    } else {
                        $serviceTypeModel = Business::serviceType()->create($entry);
                    }

                    if (! empty($serviceTypeChild)) {
                        array_walk($serviceTypeChild, function ($item) use (&$serviceTypeModel) {
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
    }

    private function serviceTypes(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => null,
                'service_type_name' => 'Fund Deposit',
                'service_type_slug' => 'fund_deposit',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'fund_deposit.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'fund_deposit.png')),
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'service_type_step' => '1',
                'enabled' => true,
                'serviceTypeChild' => [
                    [
                        'service_type_name' => 'Bank Deposit',
                        'service_type_slug' => 'bank_deposit',
                        'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'bank_deposit.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'bank_deposit.png')), 'service_type_is_parent' => 'yes',
                        'service_type_is_description' => 'no',
                        'service_type_step' => '2',
                        'enabled' => true,
                    ],
                    [
                        'service_type_name' => 'Card Deposit',
                        'service_type_slug' => 'card_deposit',
                        'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'card_deposit.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'card_deposit.png')), 'service_type_is_parent' => 'yes',
                        'service_type_is_description' => 'no',
                        'service_type_step' => '2',
                        'enabled' => true,
                    ],
                ],
            ],
            [
                'service_type_parent_id' => null, 'service_type_name' => 'Wallet to Wallet',
                'service_type_slug' => 'wallet_to_wallet',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_to_wallet.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_to_wallet.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '1',
                'enabled' => true,
            ],
        ];
    }

    private function service(): array
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
                    'account_name' => 'Lebupay ',
                    'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT',
                    'beneficiary_type_id' => null, 'operator_short_code' => null,
                ],
                'enabled' => true,
            ],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
        $serviceStats = [];
        $roles = Auth::role()->list(['id_not_in_array' => [1]])->pluck('id')->toArray();
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
                            'charge' => mt_rand(1, 7).'%',
                            'discount' => mt_rand(1, 7).'%',
                            'commission' => mt_rand(1, 7).'%',
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
