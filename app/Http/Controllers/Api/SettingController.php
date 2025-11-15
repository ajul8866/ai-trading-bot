<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

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

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('settings') as $settingData) {
            Setting::where('key', $settingData['key'])
                ->update(['value' => $settingData['value']]);
        }

        // Clear cache
        Cache::forget('all_settings');

        return response()->json([
            'message' => 'Settings updated successfully',
        ]);
    }
}
