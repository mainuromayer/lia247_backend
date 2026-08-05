<?php

use App\Http\Controllers\API\UserAuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\V1\Customer\Chat\ChatController;
use App\Http\Controllers\API\V1\Webhook\StripeWebhookController;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;





/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::controller(UserAuthController::class)->group(function () {
    // Role-specific routes are handled in their respective files
});

Broadcast::routes(['middleware' => ['jwt.verify']]);
Route::middleware(['jwt.verify'])->group(function () {
    // Role-specific protected routes are handled in their respective files
    Route::get('get-order-participants/{orderId}', [ChatController::class, 'getOrderParticipants']);
});



// --------------------------------------------------------------------------
// Role-Based API Routes (V1)
// --------------------------------------------------------------------------

// Customer Routes
require __DIR__ . '/customer/api.php';

// Vendor Routes
require __DIR__ . '/vendor/api.php';

// Rider Routes
require __DIR__ . '/rider/api.php';

// Delivery Man Routes
require __DIR__ . '/delivery_man/api.php';

// Admin Routes



// Stripe Webhook Route
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Stripe Connect Redirects
Route::get('/stripe/refresh', [App\Http\Controllers\API\V1\Common\WalletController::class, 'handleStripeRefresh'])->name('stripe.refresh');
Route::get('/stripe/return', [App\Http\Controllers\API\V1\Common\WalletController::class, 'handleStripeReturn'])->name('stripe.return');
/**
 * Firebase Token Module
 */
Route::prefix('v1/firebase/token')->controller(App\Http\Controllers\API\FirebaseTokenController::class)->group(function () {
    Route::post('/add', 'store');
    Route::post('/get', 'getToken');
    Route::post('/delete', 'deleteToken');
});

// Test route for Google Maps API Key functionality
Route::get('/test-google-map', function (Request $request) {
    $apiKey = env('GOOGLE_MAPS_API_KEY');
    
    if (empty($apiKey)) {
        return response()->json([
            'status' => 'error',
            'message' => 'GOOGLE_MAPS_API_KEY is not defined in your .env file.',
            'api_key_status' => 'Missing',
        ], 400);
    }

    $address = $request->query('address', 'Dhaka, Bangladesh');
    $client = new Client();

    try {
        // 1. Test Geocoding (Address -> Coordinates)
        $geocodeResponse = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'address' => $address,
                'key' => $apiKey,
            ],
            'http_errors' => false
        ]);
        
        $geocodeBody = json_decode($geocodeResponse->getBody()->getContents(), true);
        
        // 2. Test Reverse Geocoding (Coordinates -> Address)
        $lat = 23.8103; // Dhaka coordinates
        $lng = 90.4125;
        $reverseResponse = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'latlng' => "$lat,$lng",
                'key' => $apiKey,
            ],
            'http_errors' => false
        ]);
        
        $reverseBody = json_decode($reverseResponse->getBody()->getContents(), true);

        $geocodingSuccess = isset($geocodeBody['status']) && $geocodeBody['status'] === 'OK';
        $reverseSuccess = isset($reverseBody['status']) && $reverseBody['status'] === 'OK';

        return response()->json([
            'status' => ($geocodingSuccess && $reverseSuccess) ? 'success' : 'failed',
            'api_key' => strlen($apiKey) > 10 ? substr($apiKey, 0, 5) . '...' . substr($apiKey, -5) : 'Too short',
            'tested_address' => $address,
            'geocoding_test' => [
                'status' => $geocodeBody['status'] ?? 'UNKNOWN',
                'success' => $geocodingSuccess,
                'error_message' => $geocodeBody['error_message'] ?? null,
                'results_count' => isset($geocodeBody['results']) ? count($geocodeBody['results']) : 0,
                'full_response' => $geocodeBody,
            ],
            'reverse_geocoding_test' => [
                'status' => $reverseBody['status'] ?? 'UNKNOWN',
                'success' => $reverseSuccess,
                'error_message' => $reverseBody['error_message'] ?? null,
                'results_count' => isset($reverseBody['results']) ? count($reverseBody['results']) : 0,
                'full_response' => $reverseBody,
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Request failed: ' . $e->getMessage()
        ], 500);
    }
});

// Test route for SMTP Email functionality
Route::get('/test-email', function (Request $request) {
    $email = $request->query('email', 'test@example.com');
    
    try {
        Mail::raw('This is a test email to verify SMTP configuration.', function ($message) use ($email) {
            $message->to($email)
                    ->subject('SMTP Test Email');
        });
        

        return response()->json([
            'status' => 'success',
            'message' => "Test email successfully sent to {$email}."
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send email: ' . $e->getMessage()
        ], 500);
    }
});
