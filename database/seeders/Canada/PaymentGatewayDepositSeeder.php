<?php

namespace Fintech\Reload\Seeders\Canada;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class PaymentGatewayDepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $fundDepositParent = Business::serviceType()->list(['service_type_slug' => 'fund_deposit'])->first();

            $servingCountries = MetaData::country()->servingIds(['iso2' => 'CA']);

            $vendor = Business::serviceVendor()->list(['service_vendor_slug' => 'leatherback'])->first();

            Business::serviceTypeManager($this->data(), $fundDepositParent)
                ->srcCountries($servingCountries)
                ->vendor($vendor)
                ->enabled()
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'INTERAC E TRANSFER',
            'service_type_slug' => 'interac_e_transfer',
            'logo_svg' => "{$image_svg}interac_e_transfer.svg",
            'logo_png' => "{$image_png}interac_e_transfer.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',
            'service_stat_data' => [
                'local_currency_lower_limit' => '1000',
                'local_currency_higher_limit' => '2500',
                'transactional_currency' => 'CAD',
                'charge' => '1%',
                'discount' => '2%',
                'commission' => '0',
            ],
        ];
    }
}
