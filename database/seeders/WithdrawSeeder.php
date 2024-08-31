<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class WithdrawSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->serviceTypes() as $entry) {
                Business::serviceType()->create($entry);
            }
        }
    }

    /**
     * @return array[]
     */
    private function serviceTypes(): array
    {
        $image_svg = __DIR__ . '/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__ . '/../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => null,
                'service_type_name' => 'Withdraw',
                'service_type_slug' => 'withdraw',
                'logo_svg' => 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($image_svg . 'withdraw.svg')),
                'logo_png' => 'data:image/png;base64,' . base64_encode(file_get_contents($image_png . 'withdraw.png')),
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'service_type_step' => 1,
                'enabled' => true,
            ],
        ];
    }
}
