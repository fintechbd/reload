<?php

namespace Fintech\Reload\Commands;

use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Reload\Jobs\Deposit\InteracExpiredRequestRejectJob;
use Illuminate\Console\Command;
use Throwable;

class RejectInteractTransferExpiredRequestCommand extends Command
{
    public $signature = 'reload:reject-transfer-expired-request';

    public $description = 'schedule task to reject expired interact requests';

    public function handle(): int
    {
        try {

            $deposits = reload()->deposit()->list([
                'service_slug' => '',
                'status' => DepositStatus::Processing->value,
            ]);

            foreach ($deposits as $deposit) {
                $this->info("Deposit #{$deposit->getKey()} setting to rejected due to expiration.");
                InteracExpiredRequestRejectJob::dispatch($deposit->getKey());
            }

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

        if (business()->serviceVendor()->findWhere(['service_vendor_slug' => $vendor['service_vendor_slug']])) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            business()->serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }
}
