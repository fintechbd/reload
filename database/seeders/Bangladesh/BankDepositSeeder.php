<?php

namespace Fintech\Reload\Seeders\Bangladesh;

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

            $parent = business()->serviceType()->findWhere(['service_type_slug' => 'bank_deposit']);

            $servingCountries = MetaData::country()->servingIds(['iso2' => 'BD']);

            foreach ($this->data() as $entry) {
                business()->serviceTypeManager($entry, $parent)
                    ->hasService()
                    ->srcCountries($servingCountries)
                    ->enabled()
                    ->execute();
            }
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            [
                'service_type_name' => 'BRAC BANK LIMITED',
                'service_type_slug' => 'brac_bank_limited',
                'logo_svg' => "{$image_svg}brac_bank_limited.svg",
                'logo_png' => "{$image_png}brac_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'DUTCH-BANGLA BANK LIMITED',
                'service_type_slug' => 'dutch_bangla_bank_limited',
                'logo_svg' => "{$image_svg}dutch_bangla_bank_limited.svg",
                'logo_png' => "{$image_png}dutch_bangla_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'THE CITY BANK LIMITED',
                'service_type_slug' => 'the_city_bank_limited',
                'logo_svg' => "{$image_svg}the_city_bank_limited.svg",
                'logo_png' => "{$image_png}the_city_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'AGRANI BANK LIMITED',
                'service_type_slug' => 'agrani_bank_limited',
                'logo_svg' => "{$image_svg}agrani_bank_limited.svg",
                'logo_png' => "{$image_png}agrani_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'PUBALI BANK LIMITED',
                'service_type_slug' => 'pubali_bank_limited',
                'logo_svg' => "{$image_svg}pubali_bank_limited.svg",
                'logo_png' => "{$image_png}pubali_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'EASTERN BANK LIMITED',
                'service_type_slug' => 'eastern_bank_limited',
                'logo_svg' => "{$image_svg}eastern_bank_limited.svg",
                'logo_png' => "{$image_png}eastern_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'EXIM BANK LIMITED',
                'service_type_slug' => 'exim_bank_limited',
                'logo_svg' => "{$image_svg}exim_bank_limited.svg",
                'logo_png' => "{$image_png}exim_bank_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'ISLAMI BANK BANGLDESH LIMITED',
                'service_type_slug' => 'islami_bank_bangladesh_limited',
                'logo_svg' => "{$image_svg}islami_bank_bangladesh_limited.svg",
                'logo_png' => "{$image_png}islami_bank_bangladesh_limited.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'charge' => '1%',
                    'discount' => '0',
                    'commission' => '0',
                ],
            ],
        ];
    }
}
