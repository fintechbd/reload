<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class RequestMoneySeeder extends Seeder
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
            'service_type_name' => 'Request Money',
            'service_type_slug' => 'request_money',
            'logo_svg' => "{$image_svg}request_money.svg",
            'logo_png' => "{$image_png}request_money.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
