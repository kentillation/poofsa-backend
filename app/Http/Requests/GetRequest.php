<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id'     => 'required|integer|exists:tbl_shop_branch,branch_id',
            'search'        => 'nullable|string',
            'itemsPerPage'  => 'nullable|integer|min:1',
        ];
    }
}

// Request for the following controllers
// getOrders
// getProducts
// getProductsHistory
// getStocks
// getStocksHistory
