<?php

namespace Fintech\Reload\Seeders;

use Fintech\Core\Facades\Core;
use Fintech\Reload\Facades\Reload;
use Illuminate\Database\Seeder;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $serviceTypeData = $this->serviceType();

            foreach (array_chunk($serviceTypeData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    \Fintech\Business\Facades\Business::serviceType()->create($entry);
                }
            }

            $serviceData = $this->service();

            foreach (array_chunk($serviceData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    \Fintech\Business\Facades\Business::service()->create($entry);
                }
            }

            $serviceStatData = $this->serviceStat();
            foreach (array_chunk($serviceStatData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    \Fintech\Business\Facades\Business::serviceStat()->customStore($entry);
                }
            }
        }

        $data = $this->data();

        foreach (array_chunk($data, 200) as $block) {
            set_time_limit(2100);
            foreach ($block as $entry) {
                Reload::deposit()->create($entry);
            }
        }
    }

    private function data()
    {
        return [];
    }

    private function serviceType(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        return [
            ['id' => '1', 'service_type_parent_id' => null, 'service_type_name' => 'Fund Deposit', 'service_type_slug' => 'fund_deposit', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'fund_deposit.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'fund_deposit.png')), 'service_type_is_parent' => 'yes', 'service_type_is_description' => 'no', 'service_type_step' => '1', 'enabled' => true],
            ['id' => '2', 'service_type_parent_id' => '1', 'service_type_name' => 'Bank Deposit', 'service_type_slug' => 'bank_deposit', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'bank_deposit.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'bank_deposit.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
            /*            ['id' => '3', 'service_type_parent_id' => '3', 'service_type_name' => 'BRAC BANK LIMITED', 'service_type_slug' => 'brac_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'brac_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'brac_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '4', 'service_type_parent_id' => '3', 'service_type_name' => 'DUTCH-BANGLA BANK LIMITED', 'service_type_slug' => 'dutch_bangla_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'dutch_bangla_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'dutch_bangla_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '5', 'service_type_parent_id' => '3', 'service_type_name' => 'THE CITY BANK LIMITED', 'service_type_slug' => 'the_city_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'the_city_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'the_city_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '6', 'service_type_parent_id' => '3', 'service_type_name' => 'AGRANI BANK LIMITED', 'service_type_slug' => 'agrani_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'agrani_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'agrani_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '7', 'service_type_parent_id' => '3', 'service_type_name' => 'PUBALI BANK LIMITED', 'service_type_slug' => 'pubali_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pubali_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pubali_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '8', 'service_type_parent_id' => '3', 'service_type_name' => 'EASTERN BANK LIMITED', 'service_type_slug' => 'eastern_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'eastern_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'eastern_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '9', 'service_type_parent_id' => '3', 'service_type_name' => 'EXIM BANK LIMITED', 'service_type_slug' => 'exim_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'exim_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'exim_bank_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '10', 'service_type_parent_id' => '3', 'service_type_name' => 'ISLAMI BANK BANGLDESH LIMITED', 'service_type_slug' => 'islami_bank_bangladesh_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'islami_bank_bangladesh_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'islami_bank_bangladesh_limited.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],*/
            ['id' => '11', 'service_type_parent_id' => '1', 'service_type_name' => 'Card Deposit', 'service_type_slug' => 'card_deposit', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'card_deposit.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'card_deposit.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
            /*            ['id' => '12', 'service_type_parent_id' => '3', 'service_type_name' => 'VISA CARD', 'service_type_slug' => 'visa_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'visa_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'visa_card.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '13', 'service_type_parent_id' => '3', 'service_type_name' => 'MASTER CARD', 'service_type_slug' => 'master_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'master_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'master_card.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],
            ['id' => '14', 'service_type_parent_id' => '3', 'service_type_name' => 'DISCOVER CARD', 'service_type_slug' => 'discover_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'discover_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'discover_card.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '3', 'enabled' => true],*/
            ['id' => '15', 'service_type_parent_id' => '2', 'service_type_name' => 'PAYNOW', 'service_type_slug' => 'pay_now', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pay_now.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pay_now.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
            ['id' => '16', 'service_type_parent_id' => '2', 'service_type_name' => 'E-NETS', 'service_type_slug' => 'e_nets', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'e_nets.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'e_nets.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
            ['id' => '17', 'service_type_parent_id' => null, 'service_type_name' => 'Wallet to Wallet', 'service_type_slug' => 'wallet_to_wallet', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_to_wallet.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_to_wallet.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '1', 'enabled' => true],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service/logo_png/';

        return [
            ['id' => 1, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'BRAC BANK LIMITED', 'service_slug' => 'brac_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'brac_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'brac_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 2, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'DUTCH-BANGLA BANK LIMITED', 'service_slug' => 'dutch_bangla_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'dutch_bangla_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'dutch_bangla_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 3, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'THE CITY BANK LIMITED', 'service_slug' => 'the_city_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'the_city_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'the_city_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 4, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'AGRANI BANK LIMITED', 'service_slug' => 'agrani_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'agrani_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'agrani_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 5, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'PUBALI BANK LIMITED', 'service_slug' => 'pubali_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pubali_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pubali_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 6, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'EASTERN BANK LIMITED', 'service_slug' => 'eastern_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'eastern_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'eastern_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 7, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'EXIM BANK LIMITED', 'service_slug' => 'exim_bank_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'exim_bank_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'exim_bank_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 8, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'bank_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'ISLAMI BANK BANGLDESH LIMITED', 'service_slug' => 'islami_bank_bangladesh_limited', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'islami_bank_bangladesh_limited.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'islami_bank_bangladesh_limited.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 9, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'VISA CARD', 'service_slug' => 'visa_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'visa_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'visa_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 10, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'MASTER CARD', 'service_slug' => 'master_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'master_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'master_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 11, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'card_deposit'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'DISCOVER CARD', 'service_slug' => 'discover_card', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'discover_card.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'discover_card.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 12, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'pay_now'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'Pay Now', 'service_slug' => 'pay_now', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'pay_now.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'pay_now.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 13, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'e_nets'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'E-nets', 'service_slug' => 'e_nets', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'e_nets.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'e_nets.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
            ['id' => 14, 'service_type_id' => \Fintech\Business\Facades\Business::serviceType()->list(['service_type_slug' => 'wallet_to_wallet'])->first()->id, 'service_vendor_id' => 1, 'service_name' => 'Wallet to Wallet Transfer', 'service_slug' => 'wallet_to_wallet', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_to_wallet.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_to_wallet.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => 'Lebupay ', 'account_number' => str_pad(date('siHdmY'), 16, '0', STR_PAD_LEFT), 'transactional_currency' => 'BDT', 'beneficiary_type_id' => null, 'operator_short_code' => null], 'enabled' => true],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
        $serviceStats = [];
        foreach ($serviceLists as $serviceList) {
            $service = \Fintech\Business\Facades\Business::service()->list(['service_slug' => $serviceList['service_slug']])->first();
            $serviceStats[] = [
                'role_id' => [2, 3, 4, 5, 6],
                'service_id' => $service->getKey(),
                'service_slug' => $service->service_slug,
                'source_country_id' => [39, 133, 192, 231],
                'destination_country_id' => [19, 39, 101, 132, 133, 167, 192, 231],
                'service_vendor_id' => 1,
                'service_stat_data' => [
                    [
                        'lower_limit' => '10.00',
                        'higher_limit' => '5000.00',
                        'local_currency_higher_limit' => '25000.00',
                        'charge' => '5%',
                        'discount' => '5%',
                        'commission' => '5%',
                        'cost' => '0.00',
                        'charge_refund' => 'yes',
                        'discount_refund' => 'yes',
                        'commission_refund' => 'yes',
                    ],
                ],
                'enabled' => true,
            ];
        }

        return $serviceStats;

    }
}
