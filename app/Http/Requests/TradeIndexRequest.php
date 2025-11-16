<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradeIndexRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:OPEN,CLOSED,CANCELLED',
            'symbol' => 'sometimes|string|regex:/^[A-Z]{2,10}USDT$/',
            'from' => 'sometimes|date|before:to',
            'to' => 'sometimes|date|after:from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: OPEN, CLOSED, CANCELLED',
            'symbol.regex' => 'Symbol must be a valid trading pair (e.g., BTCUSDT)',
            'from.before' => 'From date must be before to date',
            'to.after' => 'To date must be after from date',
            'per_page.max' => 'Maximum 100 records per page',
        ];
    }
}
