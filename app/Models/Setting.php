<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * List of sensitive keys that should be encrypted
     */
    protected static array $sensitiveKeys = [
        'binance_api_key',
        'binance_api_secret',
        'openrouter_api_key',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the setting value with proper type casting and decryption for sensitive keys
     */
    public function getValueAttribute($value)
    {
        // Decrypt sensitive keys
        if (in_array($this->key, self::$sensitiveKeys) && !empty($value)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, value might not be encrypted yet
                // This handles the transition period
            }
        }

        // Apply type casting
        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the setting value with encryption for sensitive keys
     */
    public function setValueAttribute($value)
    {
        // Encrypt sensitive keys before storing
        if (in_array($this->key, self::$sensitiveKeys) && !empty($value)) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes['value'] = $value;
    }

    /**
     * Get a setting value by key with optional default
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
