<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('payments.{referenceNumber}', function ($user, $referenceNumber) {
    return true;
});