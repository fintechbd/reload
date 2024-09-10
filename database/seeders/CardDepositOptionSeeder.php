<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class CardDepositOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $parent = Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first();

            foreach ($this->data() as $entry) {
                Business::serviceTypeManager($entry, $parent)
                    ->hasService()
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
                'service_type_name' => 'Visa Card',
                'service_type_slug' => 'visa_card',
                'logo_svg' => "{$image_svg}visa_card.svg",
                'logo_png' => "{$image_png}visa_card.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'local_currency_lower_limit' => '1000',
                    'local_currency_higher_limit' => '2500',
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ],

            ],
            [
                'service_type_name' => 'Master Card',
                'service_type_slug' => 'master_card',
                'logo_svg' => "{$image_svg}master_card.svg",
                'logo_png' => "{$image_png}master_card.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'local_currency_lower_limit' => '1000',
                    'local_currency_higher_limit' => '2500',
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ],
            ],
            [
                'service_type_name' => 'Discover Card',
                'service_type_slug' => 'discover_card',
                'logo_svg' => "{$image_svg}discover_card.svg",
                'logo_png' => "{$image_png}discover_card.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_stat_data' => [
                    'local_currency_lower_limit' => '1000',
                    'local_currency_higher_limit' => '2500',
                    'charge' => '1%',
                    'discount' => '2%',
                    'commission' => '0',
                ],
            ],
        ];
    }
}
