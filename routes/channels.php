<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('payments.{referenceNumber}', function () {
    return true;
});