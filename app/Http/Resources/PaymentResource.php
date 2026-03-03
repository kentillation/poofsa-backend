<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'ok'         => $this['ok'],
            'id'         => $this['id'] ?? null,
            'status'     => $this['status'] ?? null,
            'client_key' => $this['client_key'] ?? null,
            'amount'     => $this['amount'] ?? null,
        ];
    }
}
