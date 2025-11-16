<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt existing sensitive API keys
        $sensitiveKeys = [
            'binance_api_key',
            'binance_api_secret',
            'openrouter_api_key',
        ];

        foreach ($sensitiveKeys as $key) {
            $setting = DB::table('settings')->where('key', $key)->first();

            if ($setting && !empty($setting->value)) {
                // Only encrypt if not already encrypted
                try {
                    Crypt::decryptString($setting->value);
                    // Already encrypted, skip
                } catch (\Exception $e) {
                    // Not encrypted, encrypt it
                    DB::table('settings')
                        ->where('key', $key)
                        ->update(['value' => Crypt::encryptString($setting->value)]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt settings back to plain text (for rollback)
        $sensitiveKeys = [
            'binance_api_key',
            'binance_api_secret',
            'openrouter_api_key',
        ];

        foreach ($sensitiveKeys as $key) {
            $setting = DB::table('settings')->where('key', $key)->first();

            if ($setting && !empty($setting->value)) {
                try {
                    $decrypted = Crypt::decryptString($setting->value);
                    DB::table('settings')
                        ->where('key', $key)
                        ->update(['value' => $decrypted]);
                } catch (\Exception $e) {
                    // Already decrypted or invalid, skip
                }
            }
        }
    }
};
