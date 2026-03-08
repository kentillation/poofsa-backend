<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('payments.{referenceNumber}', function ($user = null, $referenceNumber) {
    return true;
});