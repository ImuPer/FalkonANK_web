<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class DeviceFingerprintService
{
    public function generate(Request $request): string
    {
        $ua = $request->headers->get('User-Agent');
        $ip = $request->getClientIp();

        $platform = $request->headers->get('Sec-CH-UA-Platform');

        return hash('sha256', $ua . '|' . $ip . '|' . $platform);
    }
}