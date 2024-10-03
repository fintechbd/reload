<?php

namespace Fintech\Reload\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Console\Command;
use Throwable;

class LeatherBackSetupCommand extends Command
{
    public $signature = 'reload:leather-back-setup';

    public $description = 'install/update required fields for leather back';

    public function handle(): int
    {
        try {

            if (Core::packageExists('Business')) {
                $this->addServiceVendor();
                $this->addSchedulerTasks();
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

    private function addSchedulerTasks()
    {
        $tasks = [
            [
                'name' => 'Reject Expired Scheduled Requests',
                'description' => 'This schedule program will reject all the old interact requests that is still in processing.',
                'command' => 'reload:reject-transfer-expired-request',
                'enabled' => false,
                'timezone' => 'Asia/Dhaka',
                'interval' => '0 0 * * *',
                'priority' => 10,
            ],
        ];

        $this->task('Register schedule tasks', function () use (&$tasks) {
            foreach ($tasks as $task) {

                $taskModel = Core::schedule()->findWhere(['command' => $task['command']]);

                if ($taskModel) {
                    continue;
                }

                Core::schedule()->create($task);
            }
        });
    }
}
