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

            $entries = $this->data();

            $servingCountries = MetaData::country()->servingIds(['iso2' => 'CA']);

            Business::serviceTypeManager($entries[0], $fundDepositParent)
                ->srcCountries($servingCountries)
                ->enabled()
                ->execute();

            $interactParent = Business::serviceType()->list(['service_type_slug' => 'interac_e_transfer'])->first();

            Business::serviceTypeManager($entries[1], $interactParent)
                ->hasService()
                ->srcCountries($servingCountries)
                ->enabled()
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            [
                'service_type_name' => 'INTERAC E TRANSFER',
                'service_type_slug' => 'interac_e_transfer',
                'logo_svg' => "{$image_svg}interac_e_transfer.svg",
                'logo_png' => "{$image_png}interac_e_transfer.png",
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
            ],
            //child of inter e transfer
            [
                'service_type_name' => 'CIBC Bank',
                'service_type_slug' => 'cibc_bank',
                'logo_svg' => "{$image_svg}cibc_bank.svg",
                'logo_png' => "{$image_png}cibc_bank.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
        ];
    }
}
