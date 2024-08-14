<?php

namespace Fintech\Reload\Seeders\Canada;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Business\Interfaces\ServiceSeederInterface;
use Fintech\Business\Traits\ServiceSeeder;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class CanadaDepositSeeder extends Seeder implements ServiceSeederInterface
{
    use ServiceSeeder;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->serviceTypes() as $entry) {
                $serviceTypeChild = $entry['serviceTypeChild'] ?? [];
                if (isset($entry['serviceTypeChild'])) {
                    unset($entry['serviceTypeChild']);
                }

                $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();

                if ($findServiceTypeModel) {
                    $serviceTypeModel = Business::serviceType()->update($findServiceTypeModel->id, $entry);
                } else {
                    $serviceTypeModel = Business::serviceType()->create($entry);
                }

                if (! empty($serviceTypeChild)) {
                    array_walk($serviceTypeChild, function ($item) use (&$serviceTypeModel) {
                        $item['service_type_parent_id'] = $serviceTypeModel->id;
                        Business::serviceType()->create($item);
                    });
                }
            }

            $serviceData = $this->service();

            foreach (array_chunk($serviceData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::service()->create($entry);
                }
            }

            $serviceStatData = $this->serviceStat([39], [39]);

            foreach (array_chunk($serviceStatData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::serviceStat()->customStore($entry);
                }
            }
        }
    }

    public function serviceTypes(): array
    {
        $image_svg = __DIR__.'/../../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'fund_deposit'])->first()->id,
                'service_type_name' => 'INTERAC E TRANSFER',
                'service_type_slug' => 'interac_e_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'interac_e_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'interac_e_transfer.png')),
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'service_type_step' => '2',
                'enabled' => true,
                'serviceTypeChild' => [
                    [
                        'service_type_name' => 'CIBC Bank',
                        'service_type_slug' => 'cibc_bank',
                        'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'cibc_bank.svg')),
                        'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'cibc_bank.png')),
                        'service_type_is_parent' => 'no',
                        'service_type_is_description' => 'no',
                        'service_type_step' => '3',
                        'enabled' => true,
                    ],
                ],
            ],
        ];
    }

    public function service(): array
    {
        $image_svg = __DIR__.'/../../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service/logo_png/';

        return [
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'cibc_bank'])->first()->id,
                'service_vendor_id' => config('fintech.business.default_vendor', 1),
                'service_name' => 'CIBC Bank',
                'service_slug' => 'cibc_bank',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'cibc_bank.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'cibc_bank.png')),
                'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => [
                    'visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => 'Lebupay ',
                    'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT),
                    'transactional_currency' => 'CAD',
                    'beneficiary_type_id' => null,
                    'operator_short_code' => null,
                ],
                'enabled' => true,
            ],
        ];

    }
}
