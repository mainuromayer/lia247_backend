<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEtsyData extends Command
{
    protected $signature = 'etsy:sync {--keywords=gift} {--limit=100}';
    protected $description = 'Sync Etsy listings and images using official Open API v3';

    public function handle()
    {
        $keywords = $this->option('keywords');
        $limit = (int) $this->option('limit');
        $apiKey = env('ETSY_API_KEY', 'n5uk3z4h1grqzxsuqsjuipya');
        $baseUrl = rtrim(env('ETSY_BASE_URL', 'https://openapi.etsy.com/v3/application'), '/');

        $offset = 0;
        $processed = 0;

        do {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
            ])->get("{$baseUrl}/listings/active", [
                'keywords' => $keywords,
                'limit' => min($limit - $processed, 100),
                'offset' => $offset,
            ]);

            if ($response->failed()) {
                $this->error('Listings API request failed: ' . $response->status() . ' - ' . $response->body());
                break;
            }

            $data = $response->json();
            $listings = $data['results'] ?? [];

            if (empty($listings)) {
                $this->info('No more listings found.');
                break;
            }

            foreach ($listings as $item) {
                Listing::updateOrCreate(
                    ['listing_id' => $item['listing_id']],
                    [
                        'user_id' => $item['user_id'],
                        'shop_id' => $item['shop_id'],
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'state' => $item['state'],
                        'quantity' => $item['quantity'],
                        'num_favorers' => $item['num_favorers'],
                        'views' => $item['views'],
                        'price_amount' => $item['price']['amount'],
                        'currency_code' => $item['price']['currency_code'],
                        'url' => $item['url'],
                        'tags' => $item['tags'],
                        'materials' => $item['materials'],
                        'is_personalizable' => $item['is_personalizable'],
                        'personalization_is_required' => $item['personalization_is_required'],
                        'personalization_char_count_max' => $item['personalization_char_count_max'] ?? null,
                        'personalization_instructions' => $item['personalization_instructions'] ?? null,
                        'has_variations' => $item['has_variations'],
                        'who_made' => $item['who_made'],
                        'when_made' => $item['when_made'],
                        'processing_min' => $item['processing_min'] ?? 1,
                        'processing_max' => $item['processing_max'] ?? 3,
                        'created_timestamp' => $item['created_timestamp'],
                        'updated_timestamp' => $item['updated_timestamp'],
                        'last_scraped' => now(),
                    ]
                );

                $imgResponse = Http::withHeaders([
                    'x-api-key' => $apiKey,
                ])->get("{$baseUrl}/listings/{$item['listing_id']}/images");

                if ($imgResponse->successful()) {
                    foreach ($imgResponse->json()['results'] ?? [] as $img) {
                        ListingImage::updateOrCreate(
                            ['listing_image_id' => $img['listing_image_id']],
                            [
                                'listing_id' => $img['listing_id'],
                                'rank' => $img['rank'],
                                'url_75x75' => $img['url_75x75'],
                                'url_170x135' => $img['url_170x135'],
                                'url_570xN' => $img['url_570xN'],
                                'url_fullxfull' => $img['url_fullxfull'],
                                'full_width' => $img['full_width'],
                                'full_height' => $img['full_height'],
                                'hex_code' => $img['hex_code'] ?? null,
                                'red' => $img['red'] ?? null,
                                'green' => $img['green'] ?? null,
                                'blue' => $img['blue'] ?? null,
                                'hue' => $img['hue'] ?? null,
                                'saturation' => $img['saturation'] ?? null,
                                'brightness' => $img['brightness'] ?? null,
                                'is_black_and_white' => $img['is_black_and_white'] ?? false,
                                'created_timestamp' => $img['created_timestamp'],
                            ]
                        );
                    }
                } else {
                    $this->warn("Images failed for listing {$item['listing_id']}: " . $imgResponse->status());
                }

                $processed++;
            }

            $this->info("Processed offset {$offset}, fetched " . count($listings) . " listings (total: {$processed})");

            $offset += count($listings);

        } while (count($listings) > 0 && $processed < $limit);
        Log::info("Etsy sync completed. Total processed: {$processed} listings.");

        $this->info("Etsy sync completed. Total processed: {$processed} listings.");
    }
}