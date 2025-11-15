<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Format compatible with Lightweight Charts library
        if (is_array($this->resource)) {
            return [
                'time' => $this->resource['timestamp'] / 1000, // Convert to seconds
                'open' => (float) $this->resource['open'],
                'high' => (float) $this->resource['high'],
                'low' => (float) $this->resource['low'],
                'close' => (float) $this->resource['close'],
                'volume' => (float) $this->resource['volume'],
            ];
        }

        return [
            'time' => $this->timestamp?->timestamp,
            'open' => (float) $this->open,
            'high' => (float) $this->high,
            'low' => (float) $this->low,
            'close' => (float) $this->close,
            'volume' => (float) $this->volume,
        ];
    }
}
