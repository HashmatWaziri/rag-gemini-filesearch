<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class GetHealthSyncSupportContextAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $topic = 'all'): array
    {
        $context = [
            'overview' => [
                'app_name' => 'GLC Health Sync',
                'summary' => 'GLC Health Sync is the GLC AI Platform iOS companion app for automatic Apple Health syncing.',
                'syncs' => [
                    'glucose',
                    'weight',
                    'sleep',
                    'activity',
                    'vitals',
                    'other supported HealthKit data types',
                ],
                'positioning' => 'Use this as the direct answer when users ask whether GLC AI Platform has automatic health data syncing.',
            ],
            'setup' => [
                'steps' => [
                    'Open the Plate dashboard and go to Settings > Mobile Sync.',
                    'Generate an 8-character pairing token.',
                    'Install GLC Health Sync on the iPhone.',
                    'Connect by scanning the QR code or manually entering the Plate URL and pairing token.',
                    'Choose the Apple Health data permissions to share.',
                    'Sync from the app; data will flow into Plate automatically when the app runs.',
                ],
                'pairing_token' => [
                    'length' => 8,
                    'expires_after' => '24 hours',
                ],
            ],
            'platform_support' => [
                'ios' => [
                    'supported' => true,
                    'minimum_version' => config()->string('plate.health_sync.minimum_ios_version'),
                    'requirement' => 'iPhone with Apple Health data.',
                ],
                'android' => [
                    'automatic_sync_supported' => false,
                    'status' => 'Automatic Android sync is planned soon.',
                    'today' => 'Android users can use the GLC AI Platform PWA and manual logging today.',
                ],
            ],
            'privacy' => [
                'apple_health_access' => 'Reads Apple Health only after the user grants permission.',
                'encryption' => 'Encrypts health data on the device before sending it.',
                'destination' => "Sends data directly to the user's GLC AI Platform instance.",
                'apple_health_write_access' => 'GLC Health Sync does not write data to Apple Health.',
            ],
            'troubleshooting' => [
                'token_expired' => 'Generate a fresh token from Settings > Mobile Sync. Pairing tokens last 24 hours.',
                'pairing_fails' => 'Check the platform instance URL and make sure the token is current.',
                'no_data_showing' => 'Confirm Apple Health has data and HealthKit permissions are enabled in GLC Health Sync.',
                'sync_fails' => 'Make sure the platform instance is online, then try Sync Now in the app.',
                'support' => 'Contact GLC administration for pairing, syncing, or HealthKit permission help.',
            ],
            'links' => [
                'app_store_url' => config()->string('plate.health_sync.app_store_url'),
                'health_sync_url' => url('/'),
                'setup_guide_url' => url('/'),
                'mobile_sync_settings_url' => route('mobile-sync.edit'),
                'install_app_url' => url('/'),
                'support_email' => (string) config('mail.from.address'),
            ],
        ];

        if ($topic === 'all') {
            return $context;
        }

        return [
            $topic => $context[$topic] ?? $context['overview'],
            'links' => $context['links'],
        ];
    }
}
