<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class ForeignExchangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $servingCountries = MetaData::country()->servingIds();

            Business::serviceTypeManager($this->data())
                ->hasService()
                ->disabled()
                ->distCountries($servingCountries)
                ->hasTransactionForm()
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'Foreign Exchange',
            'service_type_slug' => 'foreign_exchange',
            'logo_svg' => "{$image_svg}foreign_exchange.svg",
            'logo_png' => "{$image_png}foreign_exchange.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',
            'service_stat_data' => [
                'charge' => '1%',
                'discount' => '2%',
                'commission' => '0',
            ],

        ];
    }
}
