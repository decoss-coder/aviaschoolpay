<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayDunyaService
{
    private string $baseUrl;
    private string $masterKey;
    private string $privateKey;
    private string $publicKey;
    private string $token;

    public function __construct()
    {
        $mode = config('services.paydunya.mode', 'test');
        $this->baseUrl = $mode === 'live'
            ? 'https://app.paydunya.com/api/v1'
            : 'https://app.paydunya.com/sandbox-api/v1';
        $this->masterKey = config('services.paydunya.master_key');
        $this->privateKey = config('services.paydunya.private_key');
        $this->publicKey = config('services.paydunya.public_key');
        $this->token = config('services.paydunya.token');
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
            'PAYDUNYA-PUBLIC-KEY' => $this->publicKey,
            'PAYDUNYA-TOKEN' => $this->token,
        ];
    }

    /**
     * Créer une facture PayDunya
     */
    public function creerFacture(int $montant, string $description, string $reference, string $callbackUrl): array
    {
        try {
            $response = Http::withHeaders($this->headers())->post("{$this->baseUrl}/checkout-invoice/create", [
                'invoice' => [
                    'total_amount' => $montant,
                    'description' => $description,
                ],
                'store' => [
                    'name' => config('app.name', 'AviaSchoolPay'),
                    'tagline' => 'Paiement de scolarité',
                    'website_url' => config('app.url'),
                ],
                'custom_data' => [
                    'reference' => $reference,
                    'source' => 'aviaschoolpay',
                ],
                'actions' => [
                    'callback_url' => $callbackUrl,
                    'return_url' => config('app.url') . '/paiement/succes',
                    'cancel_url' => config('app.url') . '/paiement/annule',
                ],
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['response_code'] ?? '') === '00') {
                return [
                    'success' => true,
                    'token' => $data['token'],
                    'invoice_url' => $data['response_text'] ?? $data['invoice_url'] ?? null,
                ];
            }

            Log::error('PayDunya - Échec création facture', ['response' => $data]);
            return ['success' => false, 'error' => $data['response_text'] ?? 'Erreur inconnue'];

        } catch (\Exception $e) {
            Log::error('PayDunya - Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Vérifier le statut d'une facture
     */
    public function verifierFacture(string $token): array
    {
        try {
            $response = Http::withHeaders($this->headers())->get("{$this->baseUrl}/checkout-invoice/confirm/{$token}");
            $data = $response->json();

            return [
                'status' => $data['status'] ?? 'unknown',
                'response_code' => $data['response_code'] ?? null,
                'response_text' => $data['response_text'] ?? null,
                'invoice' => $data['invoice'] ?? null,
                'customer' => $data['customer'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('PayDunya - Erreur vérification', ['token' => $token, 'error' => $e->getMessage()]);
            return ['status' => 'error', 'response_text' => $e->getMessage()];
        }
    }

    /**
     * Créer un lien de paiement personnalisé (pour relances SMS)
     */
    public function creerLienPaiement(int $montant, string $eleveNom, string $reference): ?string
    {
        $result = $this->creerFacture($montant, "Scolarité de $eleveNom", $reference, route('api.paiements.callback.paydunya'));
        return $result['success'] ? $result['invoice_url'] : null;
    }
}
