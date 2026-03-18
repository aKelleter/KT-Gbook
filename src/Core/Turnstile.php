<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

final class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function isEnabled(): bool
    {
        return Config::bool('TURNSTILE_ENABLED', false);
    }

    public static function siteKey(): string
    {
        return trim((string) Config::get('TURNSTILE_SITE_KEY', ''));
    }

    private static function secretKey(): string
    {
        return trim((string) Config::get('TURNSTILE_SECRET_KEY', ''));
    }

    public static function verify(?string $token, ?string $remoteIp = null): array
    {
        if (!self::isEnabled()) {
            return ['success' => true, 'message' => null];
        }

        if (self::siteKey() === '' || self::secretKey() === '') {
            return [
                'success' => false,
                'message' => 'Le captcha est activé mais les clés Turnstile ne sont pas configurées.',
            ];
        }

        $token = trim((string) $token);
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'Merci de valider la vérification anti-spam.',
            ];
        }

        $payload = [
            'secret' => self::secretKey(),
            'response' => $token,
        ];

        $remoteIp = trim((string) $remoteIp);
        if ($remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $response = self::post(self::VERIFY_URL, $payload);
        if ($response === null) {
            return [
                'success' => false,
                'message' => 'Impossible de vérifier le captcha pour le moment. Merci de réessayer dans un instant.',
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Réponse captcha invalide.',
            ];
        }

        if (($decoded['success'] ?? false) === true) {
            return ['success' => true, 'message' => null];
        }

        $errorCodes = $decoded['error-codes'] ?? [];
        $message = 'La vérification anti-spam a échoué. Merci de réessayer.';

        if (is_array($errorCodes) && in_array('timeout-or-duplicate', $errorCodes, true)) {
            $message = 'Le captcha a expiré. Merci de refaire la vérification.';
        }

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    
    private static function post(string $url, array $payload): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                return (string) $response;
            }

            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return $response === false ? null : (string) $response;
    }

    /*
    // post With logging for debugging purposes    
    private static function post(string $url, array $payload): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                return (string) $response;
            }

            error_log('Turnstile cURL error: ' . $curlError . ' / HTTP ' . $httpCode);
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            error_log('Turnstile stream error: ' . ($error['message'] ?? 'unknown'));
            return null;
        }

        return (string) $response;
    }
    */
}
