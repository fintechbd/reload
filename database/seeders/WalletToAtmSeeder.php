<?php

namespace Fintech\Reload\Seeders;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Seeder;

class WalletToAtmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $parent = Business::serviceType()->list(['service_type_slug' => 'withdraw'])->first();

            Business::serviceTypeManager($this->data(), $parent)
                ->hasService()
                ->hasTransactionForm()
                ->enabled()
                ->execute();
        }

        if (Core::packageExists('Transaction') && ! Transaction::transactionForm()->list(['code' => 'local_atm_transfer'])->first()) {
            Transaction::transactionForm()->create([
                'name' => 'Local ATM Transfer',
                'code' => 'local_atm_transfer',
                'enabled' => true,
                'transaction_form_data' => [],
            ]);
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/reload/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/reload/resources/img/service_type/logo_png/');

        return [
            'service_type_name' => 'Local ATM Transfer',
            'service_type_slug' => 'local_atm_transfer',
            'logo_svg' => "{$image_svg}local_atm_transfer.svg",
            'logo_png' => "{$image_png}local_atm_transfer.png",
            'service_type_is_parent' => 'no',
            'service_type_is_description' => 'no',

        ];
    }
}
