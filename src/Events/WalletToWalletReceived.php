<?php

namespace Fintech\Reload\Events;

use Fintech\Business\Facades\Business;
use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletToWalletReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $walletToWallet;

    /**
     * Create a new event instance.
     */
    public function __construct($walletToWallet)
    {
        $timeline = $walletToWallet->timeline;

        $service = Business::service()->find($walletToWallet->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' wallet to wallet transfer request received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->walletToWallet = Reload::walletToWallet()->update($walletToWallet->getKey(), ['timeline' => $timeline]);
    }
}
