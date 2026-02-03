<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    /**
     * Generate a consistent device fingerprint
     */
    public static function generate(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent() ?? '',
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            $request->header('sec-ch-ua', ''),
            $request->header('sec-ch-ua-platform', ''),
            $request->ip() ?? ''
        ]));
    }
}