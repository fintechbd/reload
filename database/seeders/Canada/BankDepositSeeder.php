<?php

namespace Fintech\Reload\Seeders\Canada;

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

            $parent = Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first();

            $servingCountries = MetaData::country()->servingIds(['iso2' => 'CA']);

            $entries = $this->data();

            Business::serviceTypeManager($entries[0], $parent)
                ->hasService()
                ->srcCountries($servingCountries)
                ->execute();

            Business::serviceTypeManager($entries[1], $parent)
                ->hasService()
                ->srcCountries($servingCountries)
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            [
                'service_type_name' => 'PEOPLES TRUST COMPANY',
                'service_type_slug' => 'peoples_trust_company',
                'logo_svg' => "{$image_svg}peoples_trust_company.svg",
                'logo_png' => "{$image_png}peoples_trust_company.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'enabled' => true,
                'service_settings' => [
                    'account_name' => 'CLAVIS FINTECH SOLUTIONS LTD',
                    'account_number' => '400000000478',
                    'transactional_currency' => 'CAD',
                    'routing_code' => '62120002',
                ],
                'service_stat_data' => [
                    'local_currency_lower_limit' => '1000',
                    'local_currency_higher_limit' => '2500',
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ]
            ],
            [
                'service_type_name' => 'INTERAC CANADA',
                'service_type_slug' => 'interac_canada',
                'logo_svg' => "{$image_svg}interac_canada.svg",
                'logo_png' => "{$image_png}interac_canada.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'enabled' => false,
                'service_stat_data' => [
                    'local_currency_lower_limit' => '1000',
                    'local_currency_higher_limit' => '2500',
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ],
                'service_settings' => [
                    'account_name' => ' CLAVIS FINTECH SOLUTIONS LTD',
                    'account_number' => '400000000478@leatherbackcanada.com',
                    'transactional_currency' => 'CAD',
                    'interac' => 'ALIAS_REGULAR',
                ]
            ],
        ];
    }
}
