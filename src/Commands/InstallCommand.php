<?php

namespace Fintech\Reload\Commands;

use Fintech\Core\Traits\HasCoreSettingTrait;
use Fintech\Transaction\Seeders\TransactionFormSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    use HasCoreSettingTrait;

    public $signature = 'reload:install';

    public $description = 'Configure the system for the `fintech/reload` module';

    private array $settings = [
        [
            'package' => 'transaction',
            'label' => 'Transaction Delay Time',
            'description' => 'Transaction Delay Time',
            'key' => 'delay_time',
            'type' => 'integer',
            'value' => '15',
        ],
        [
            'package' => 'transaction',
            'label' => 'Transaction Minimum Balance',
            'description' => 'Transaction Minimum Balance',
            'key' => 'minimum_balance',
            'type' => 'float',
            'value' => '0.00',
        ],
    ];

    private string $module = 'Reload';

    public function handle(): int
    {
        $this->addSettings();

        $this->addUtilityOptions();

        $this->components->twoColumnDetail("[<fg=yellow;options=bold>{$this->module}</>] Installation", '<fg=green;options=bold>COMPLETED</>');

        return self::SUCCESS;
    }

    private function addUtilityOptions(): void
    {
        $seeders = [
            TransactionFormSeeder::class => 'transaction form',
        ];

        foreach ($seeders as $class => $label) {
            $this->components->task("[<fg=yellow;options=bold>{$this->module}</>] Populating {$label} data", function () use ($class) {
                Artisan::call('db:seed --class='.addslashes($class).' --quiet');
            });
        }
    }
}
