<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'envoi SMS via Infobip.
 * Configuration via .env :
 *   INFOBIP_BASE_URL   = ex: https://xrewjl.api.infobip.com
 *   INFOBIP_API_KEY    = clé API
 *   INFOBIP_SENDER     = identifiant expéditeur (ex: AviaSchool ou numéro)
 *   INFOBIP_TIMEOUT    = en secondes (défaut 15)
 */
class InfobipSmsService
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $apiKey = null,
        private ?string $sender = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.infobip.base_url', env('INFOBIP_BASE_URL')), '/');
        $this->apiKey  = $apiKey  ?? (string) config('services.infobip.api_key',  env('INFOBIP_API_KEY'));
        $this->sender  = $sender  ?? (string) config('services.infobip.sender',   env('INFOBIP_SENDER', 'AviaSchool'));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->apiKey);
    }

    /**
     * Envoyer un SMS.
     *
     * @return array{success: bool, message_id?: string, status?: string, response?: array, error?: string}
     */
    public function send(string $destinataire, string $contenu, ?string $reference = null): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => 'Service SMS non configuré (INFOBIP_BASE_URL / INFOBIP_API_KEY manquants).'];
        }

        $destNormalise = $this->normaliserNumero($destinataire);
        if (! $destNormalise) {
            return ['success' => false, 'error' => "Numéro invalide : $destinataire"];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App '.$this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])
            ->timeout((int) env('INFOBIP_TIMEOUT', 15))
            ->post($this->baseUrl.'/sms/2/text/advanced', [
                'messages' => [[
                    'from' => $this->sender,
                    'destinations' => [['to' => $destNormalise]],
                    'text' => $this->nettoyerTexte($contenu),
                    ...($reference ? ['callbackData' => $reference] : []),
                ]],
            ]);

            $json = $response->json() ?? [];

            if ($response->successful() && isset($json['messages'][0])) {
                $msg = $json['messages'][0];
                return [
                    'success'    => true,
                    'message_id' => $msg['messageId'] ?? null,
                    'status'     => $msg['status']['name'] ?? 'PENDING',
                    'response'   => $json,
                ];
            }

            Log::warning('Infobip SMS failed', ['dest' => $destNormalise, 'status' => $response->status(), 'body' => $json]);
            return [
                'success' => false,
                'error'   => $json['requestError']['serviceException']['text'] ?? 'Erreur Infobip (HTTP '.$response->status().')',
                'response' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('Infobip SMS exception', ['dest' => $destNormalise, 'msg' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erreur réseau : '.$e->getMessage()];
        }
    }

    /**
     * Normalise un numéro en format international (E.164) pour la Côte d'Ivoire (+225).
     */
    public function normaliserNumero(string $num): ?string
    {
        $clean = preg_replace('/[^0-9+]/', '', $num);
        if (! $clean) return null;

        // Déjà international ?
        if (str_starts_with($clean, '+')) {
            // garde tel quel mais sans le +
            $clean = ltrim($clean, '+');
            return preg_match('/^\d{10,15}$/', $clean) ? $clean : null;
        }

        // Numéro CI à 10 chiffres → préfixer 225
        if (preg_match('/^\d{10}$/', $clean)) {
            return '225'.$clean;
        }

        // Numéro déjà avec 225 préfixe
        if (preg_match('/^225\d{10}$/', $clean)) {
            return $clean;
        }

        // Autre cas : refuse
        return null;
    }

    /**
     * Compte le nombre de SMS (1 SMS = 160 car GSM-7, 70 si caractères spéciaux).
     */
    public static function nbParties(string $contenu): int
    {
        $len = mb_strlen($contenu);
        $hasUnicode = (bool) preg_match('/[^\x00-\x7F]/', $contenu);
        $perPart = $hasUnicode ? 70 : 160;
        return (int) max(1, ceil($len / $perPart));
    }

    private function nettoyerTexte(string $contenu): string
    {
        return trim(preg_replace('/\s+/u', ' ', $contenu));
    }
}
