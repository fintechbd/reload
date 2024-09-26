<?php

/*|--------------------------------------------------------------------------
|  Language Lines
|--------------------------------------------------------------------------
|
| The following language lines are used during authentication for various
| messages that we need to display to the user. You are free to modify
| these language lines according to your application's requirements.
|
*/

return [
    'action' => [
        'reject' => 'Reject',
        'accept' => 'Accept',
        'cancel' => 'Cancel',
    ],
    'deposit' => [
        'invalid_status' => 'Deposit with :current_status status can not changed to :target_status.',
        'status_change_failed' => 'Failed to change from :current_status to :target_status status.',
        'status_change_success' => 'Deposit moved to :status status successfully.',
        'failed' => 'Your transaction for Bank Deposit Request is Failed',
        'created' => 'Your transaction for Bank Deposit Request is Successfully Submitted',
    ],
];
