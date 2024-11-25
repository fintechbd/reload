<?php

namespace Fintech\Reload\Http\Controllers\Charts;

use Fintech\Reload\Http\Resources\Charts\DepositPartnerCollection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DepositPartnerController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $collection = collect([
            ['service_type' => 'Bank Account', 'service_name' => 'RBC', 'account_number' => '15787477', 'limits' => '1000', 'charge' => '2.50'],
            ['service_type' => 'Bank Account', 'service_name' => 'CIBC', 'account_number' => '9807979991', 'limits' => '1500', 'charge' => '2.00'],
            ['service_type' => 'Card', 'service_name' => 'Credit Card', 'account_number' => '........895', 'limits' => '500', 'charge' => '1.20%'],
            ['service_type' => 'Paypal', 'service_name' => 'Paypal', 'account_number' => '1223341334', 'limits' => '2000', 'charge' => '0.8%'],
            ['service_type' => 'Paypal', 'service_name' => 'Paypal', 'account_number' => '1223341334', 'limits' => '2000', 'charge' => '0.8%'],
        ]);

        return new DepositPartnerCollection($collection);
    }
}
