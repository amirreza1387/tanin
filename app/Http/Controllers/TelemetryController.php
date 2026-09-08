<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:160'],
            'metrics' => ['required', 'array'],
            'metrics.lcp' => ['nullable', 'numeric', 'min:0', 'max:120000'],
            'metrics.cls' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'metrics.inp' => ['nullable', 'numeric', 'min:0', 'max:120000'],
            'metrics.ttfb' => ['nullable', 'numeric', 'min:0', 'max:120000'],
        ]);

        Log::channel(config('logging.default'))->info('frontend.performance', $data);

        return response()->noContent();
    }
}
