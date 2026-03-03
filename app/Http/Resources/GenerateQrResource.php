<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerateQrResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public static $wrap = null;
    
    public function toArray($request): array
    {
        return [
            'payment_intent_id' => $this['payment_intent_id'],
            'qr_image'          => $this['qr_image'],
            'status'            => $this['status'],
        ];
    }
}
