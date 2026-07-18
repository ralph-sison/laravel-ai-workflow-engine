<?php

namespace App\Services;

use Illuminate\Http\Request;

class WebhookSignatureVerifier
{
    /**
     * Verify HMAC-SHA256 signature sent in X-FlowForge-Signature header.
     *
     * Senders must compute: HMAC-SHA256(secret, raw_body) and send as hex.
     * Header format: sha256=<hex_digest>
     */
    public function verify(Request $request, string $secret): bool
    {
        $signature = $request->header('X-FlowForge-Signature');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
