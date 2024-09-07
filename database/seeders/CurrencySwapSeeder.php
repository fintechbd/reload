<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class CurrencySwapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $servingCountries = MetaData::country()->servingIds();;

            Business::serviceTypeManager($this->data())
                ->hasService()
                ->srcCountries($servingCountries)
                ->distCountries($servingCountries)
                ->hasTransactionForm()
                ->enabled()
                ->execute();
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'Currency Swap',
            'service_type_slug' => 'currency_swap',
            'logo_svg' => "{$image_svg}currency_swap.svg",
            'logo_png' => "{$image_png}currency_swap.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
