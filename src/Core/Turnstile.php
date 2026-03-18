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

    /**
     * Vérifie la validité du token Turnstile en effectuant une requête à l'API de vérification.
     * Gère les différents scénarios d'erreur et retourne un tableau indiquant le succès et un message d'erreur le cas échéant.
     * @param string|null $token Le token Turnstile à vérifier.
     * @param string|null $remoteIp L'adresse IP du client (optionnel).
     * @return array Un tableau associatif avec les clés 'success' (bool) et 'message' (string|null) indiquant le résultat de la vérification.
     */
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

    /**
     * Effectue une requête POST vers l'API de vérification de Turnstile.
     * Utilise cURL si disponible, sinon fallback sur file_get_contents avec un contexte de flux.
     * Gère les erreurs de connexion et de réponse, et respecte la configuration de vérification SSL.
     * @return string|null La réponse brute de l'API en cas de succès, ou null en cas d'erreur.     * 
     */
    private static function post(string $url, array $payload): ?string
    {
        $verifySsl = Config::envBool('TURNSTILE_VERIFY_SSL', true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => $verifySsl,
                CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                return (string) $response;
            }

            error_log(sprintf(
                'Turnstile cURL error: %s / HTTP %d / verify_ssl=%s',
                $curlError !== '' ? $curlError : 'none',
                $httpCode,
                $verifySsl ? 'true' : 'false'
            ));

            return null;
        }

        $sslOptions = [
            'verify_peer' => $verifySsl,
            'verify_peer_name' => $verifySsl,
        ];

        if (!$verifySsl) {
            $sslOptions['allow_self_signed'] = true;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
            'ssl' => $sslOptions,
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();

            error_log(sprintf(
                'Turnstile stream error: %s / verify_ssl=%s',
                $error['message'] ?? 'unknown',
                $verifySsl ? 'true' : 'false'
            ));

            return null;
        }

        return (string) $response;
    }
}
