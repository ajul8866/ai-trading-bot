<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Cache::remember('all_settings', 300, function () {
            return Setting::all()->mapWithKeys(function ($setting) {
                return [$setting->key => [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                ]];
            });
        });

        return response()->json($settings);
    }

    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->value,
            'type' => $setting->type,
            'description' => $setting->description,
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            foreach ($validated['settings'] as $settingData) {
                $updated = Setting::where('key', $settingData['key'])
                    ->update(['value' => $settingData['value']]);

                if (!$updated) {
                    Log::warning('Setting not found for update', ['key' => $settingData['key']]);
                }
            }

            // Clear cache
            Cache::forget('all_settings');

            return response()->json([
                'message' => 'Settings updated successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to update settings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
