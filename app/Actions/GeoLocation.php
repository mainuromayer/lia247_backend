<?php

namespace App\Actions;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeoLocation
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client;
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
    }

    public function getCoordinates($address): array
    {
        if (empty(trim($address))) {
            Log::info('Geolocation skipped: Empty address provided');
            return ['latitude' => null, 'longitude' => null];
        }

        try {
            $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'address' => $address,
                    'key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            Log::info('Geocoding Full Response: ' . $data);

            if ($data['status'] === 'OK') {
                return [
                    'latitude' => $data['results'][0]['geometry']['location']['lat'],
                    'longitude' => $data['results'][0]['geometry']['location']['lng'],
                ];
            }
            Log::error('Geolocation error: ' . ($data['error_message'] ?? 'Unknown error occurred'));
            return ['latitude' => null, 'longitude' => null];
        }
        catch (Exception $e) {
            Log::error('Geolocation request failed: ' . $e->getMessage());
            return ['latitude' => null, 'longitude' => null];
        }
    }

    public function getAddress($lat, $lng): ?string
    {
        try {
            $apiKey = env('GOOGLE_MAPS_API_KEY');

            if (empty($apiKey)) {
                Log::info("GOOGLE_MAPS_API_KEY is empty. Falling back to OpenStreetMap Nominatim.");
                $response = $this->client->get('https://nominatim.openstreetmap.org/reverse', [
                    'headers' => [
                        'User-Agent' => 'Lia247Backend/1.0 (contact@lia247.com)',
                    ],
                    'query' => [
                        'lat' => $lat,
                        'lon' => $lng,
                        'format' => 'json',
                    ],
                ]);
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                if (isset($data['display_name'])) {
                    return $data['display_name'];
                }
                return null;
            }

            Log::info("Geocoding Request: lat=$lat, lng=$lng, key=" . substr($apiKey, 0, 5) . "...");

            $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => "$lat,$lng",
                    'key' => $apiKey,
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            Log::info('Geocoding Full Response: ' . $body);

            if (isset($data['status']) && $data['status'] === 'OK') {
                return $data['results'][0]['formatted_address'];
            }
            
            $errorMsg = $data['error_message'] ?? ($data['status'] ?? 'Unknown status');
            Log::error('Reverse Geolocation error: ' . $errorMsg);
            return null;
        }
        catch (Exception $e) {
            Log::error('Reverse Geolocation request failed: ' . $e->getMessage());
            return null;
        }
    }
}