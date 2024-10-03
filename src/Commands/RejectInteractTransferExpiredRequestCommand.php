<?php

namespace Fintech\Reload\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Facades\Core;
use Fintech\Reload\Facades\Reload;
use Illuminate\Console\Command;
use Throwable;

class RejectInteractTransferExpiredRequestCommand extends Command
{
    public $signature = 'reload:reject-transfer-expired-request';

    public $description = 'schedule task to reject expired interact requests';

    public function handle(): int
    {
        try {

            $deposits = Reload::deposit()->list([
                'service_slug' => '',
                'status' => DepositStatus::Processing->value
            ]);

            foreach ($deposits as $deposit) {
                $this->info("Deposit #{$deposit->getKey()} setting to rejected due to expiration.");

            }

            if (Core::packageExists('Business')) {
                $this->addServiceVendor();
            } else {
                $this->info('`fintech/business` is not installed. Skipped');
            }

            $this->info('Leather Back reload service vendor setup completed.');

            return self::SUCCESS;

        } catch (Throwable $th) {

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__.'/../../resources/img/service_vendor/';

        $vendor = [
            'service_vendor_name' => 'Leather Back',
            'service_vendor_slug' => 'leatherback',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents("{$dir}/logo_png/LB-Logo-Blue.png")),
            'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents("{$dir}/logo_svg/LB-Logo-Blue.svg")),
            'enabled' => false,
        ];

        if (Business::serviceVendor()->findWhere(['service_vendor_slug' => $vendor['service_vendor_slug']])) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }
}
