<?php

namespace Fintech\Reload\Commands;

use Fintech\Core\Traits\HasCoreSetting;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    use HasCoreSetting;

    public $signature = 'reload:install';

    public $description = 'Configure the system for the `fintech/reload` module';

    private string $module = 'Reload';

    private string $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';

    private string $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

    public function handle(): int
    {
        $this->infoMessage('Module Installation', 'RUNNING');

        $this->task('Module Installation', function () {

            $this->addDefaultServiceTypes();

            $this->addBankCardDeposit();

        });

        return self::SUCCESS;
    }

    private function addDefaultServiceTypes(): void
    {
        $this->task('Creating system default service types', function () {

            $serviceTypes = [
                [
                    'service_type_name' => 'Fund Deposit',
                    'service_type_slug' => 'fund_deposit',
                    'logo_svg' => $this->image_svg.'fund_deposit.svg',
                    'logo_png' => $this->image_png.'fund_deposit.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '1',
                ],
                [
                    'service_type_name' => 'Withdraw',
                    'service_type_slug' => 'withdraw',
                    'logo_svg' => $this->image_svg.'withdraw.svg',
                    'logo_png' => $this->image_png.'withdraw.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '1',
                ],
            ];
            foreach ($serviceTypes as $entry) {
                business()->serviceTypeManager($entry)
                    ->disabled()
                    ->execute();
            }
        });
    }

    private function addBankCardDeposit(): void
    {
        $this->task('Populating Fund Deposit (Bank & Card) Service Types', function () {
            $parent = business()->serviceType()->findWhere(['service_type_slug' => 'fund_deposit']);
            $types = [
                [
                    'service_type_name' => 'Bank Deposit',
                    'service_type_slug' => 'bank_deposit',
                    'logo_svg' => $this->image_svg.'bank_deposit.svg',
                    'logo_png' => $this->image_png.'bank_deposit.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '2',
                    'enabled' => true,
                ],
                [
                    'service_type_parent_id' => business()->serviceType()->findWhere(['service_type_slug' => 'fund_deposit'])->id,
                    'service_type_name' => 'Card Deposit',
                    'service_type_slug' => 'card_deposit',
                    'logo_svg' => $this->image_svg.'card_deposit.svg',
                    'logo_png' => $this->image_png.'card_deposit.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '2',
                    'enabled' => false,
                ],
            ];
            foreach ($types as $entry) {
                business()->serviceTypeManager($entry, $parent)->execute();
            }
        });
    }
}
