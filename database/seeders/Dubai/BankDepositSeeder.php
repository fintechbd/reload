<?php

namespace Fintech\Reload\Seeders\Dubai;

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

            $servingCountries = MetaData::country()->servingIds(['iso2' => 'AE']);

            foreach ($this->data() as $entry) {
                $entry['service_stat_data'] = [
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ];

                Business::serviceTypeManager($entry, $parent)
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
                'service_type_name' => 'ABU DHABI COMMERCIAL BANK (ADCB)',
                'service_type_slug' => 'abu_dhabi_commercial_bank',
                'logo_svg' => $image_svg.'abu_dhabi_commercial_bank.svg',
                'logo_png' => $image_png.'abu_dhabi_commercial_bank.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'DUBAI ISLAMI BANK',
                'service_type_slug' => 'dubai_islami_bank',
                'logo_svg' => $image_svg.'dubai_islami_bank.svg',
                'logo_png' => $image_png.'dubai_islami_bank.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'MASHREQBANK',
                'service_type_slug' => 'mashreqbank',
                'logo_svg' => $image_svg.'mashreqbank.svg',
                'logo_png' => $image_png.'mashreqbank.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'NATIONAL BANK OF RAS AL KHAIMAH (RAKBANK)',
                'service_type_slug' => 'rakbank',
                'logo_svg' => $image_svg.'rakbank.svg',
                'logo_png' => $image_png.'rakbank.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
        ];
    }
}
