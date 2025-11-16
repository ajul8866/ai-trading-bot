<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic when authentication is implemented
    }

    /**
     * Whitelist of allowed settings that can be modified via API
     */
    protected array $allowedSettings = [
        'bot_enabled',
        'trading_pairs',
        'timeframes',
        'max_positions',
        'risk_per_trade',
        'daily_loss_limit',
        'min_confidence',
        'analysis_interval',
        'ai_model',
        'ai_prompt_system',
        'ai_prompt_templates',
        'ai_prompt_risk_profile',
        'cache_ttl_prices',
        'cache_ttl_charts',
        'ui_refresh_interval',
    ];

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array|min:1',
            'settings.*.key' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!in_array($value, $this->allowedSettings)) {
                        $fail("The setting '$value' is not allowed to be modified via API.");
                    }
                },
            ],
            'settings.*.value' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Settings array is required.',
            'settings.array' => 'Settings must be an array.',
            'settings.min' => 'At least one setting must be provided.',
            'settings.*.key.required' => 'Setting key is required.',
            'settings.*.value.required' => 'Setting value is required.',
        ];
    }
}
