<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('eWalletPaymentSuccessBroadcast', function ($user) {
    return true;
});

Broadcast::channel('eWalletPaymentFailedBroadcast', function ($user) {
    return true;
});
