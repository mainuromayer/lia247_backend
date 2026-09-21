<?php

namespace App\Helper;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\Notification;
use Stripe\Account;
use Stripe\Stripe;
use Throwable;

class Helper
{
    use ApiResponse;

    public static function fileUpload($file, $folder, $name = null)
    {
        try {
            if (!$file) {
                return null;
            }

            if (is_array($file)) {
                $paths = [];
                foreach ($file as $key => $singleFile) {
                    if ($singleFile) {
                        $paths[] = self::handleUpload($singleFile, $folder, $name . ($name ? "-$key" : ""));
                    }
                }
                return $paths;
            }

            return self::handleUpload($file, $folder, $name);
        } catch (Throwable $e) {
            Log::error("File upload error: " . $e->getMessage());
            return null;
        }
    }

    private static function handleUpload($file, $folder, $name = null)
    {
        if (!$file || !is_object($file) || !method_exists($file, 'getClientOriginalName')) {
            return null;
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filenameOnly = pathinfo($originalName, PATHINFO_FILENAME);

        // Use provided name if exists, otherwise use original filename
        $baseName = $name ? Str::slug($name) : Str::slug($filenameOnly);

        $filename = $baseName . '.' . $extension;
        $destinationPath = public_path('uploads/' . $folder);

        // Ensure directory exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $counter = 1;
        while (file_exists($destinationPath . '/' . $filename)) {
            $filename = $baseName . '(' . $counter . ').' . $extension;
            $counter++;
        }

        $file->move($destinationPath, $filename);
        return 'uploads/' . $folder . '/' . $filename;
    }

    //tableCheckbox
    public static function tableCheckbox($row_id)
    {
        return '<div class="form-checkbox">
                    <input type="checkbox" class="form-check-input select_data" id="checkbox-' . $row_id . '" value="' . $row_id . '" onClick="select_single_item(' . $row_id . ')">
                    <label class="form-check-label" for="checkbox-' . $row_id . '"></label>
                </div>';
    }

    //video upload
    public static function videoUpload($file, $folder, $name)
    {
        try {
            $videoName = Str::slug($name) . '.' . $file->extension();
            $file->move(public_path('uploads/' . $folder), $videoName);
            $path = 'uploads/' . $folder . '/' . $videoName;
            return $path;
        }
        catch (Exception $e) {
            Log::error("Video upload error: " . $e->getMessage());
            return null;
        }
    }

    // audio upload
    public static function audioUpload($file, $folder, $name)
    {
        try {
            $audioName = Str::slug($name) . '.' . $file->extension();
            $file->move(public_path('uploads/' . $folder), $audioName);
            $path = 'Uploads/' . $folder . '/' . $audioName;
            return $path;
        }
        catch (Exception $e) {
            Log::error("Audio upload error: " . $e->getMessage());
            return null;
        }
    }

    public static function deleteFile($path)
    {
        try {
            if (!$path) return false;

            if (is_array($path)) {
                foreach ($path as $singlePath) {
                    self::deleteFile($singlePath);
                }
                return true;
            }

            // check if it is json
            if (is_string($path)) {
                $decoded = json_decode($path, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $singlePath) {
                        self::deleteFile($singlePath);
                    }
                    return true;
                }
            }

            if (file_exists(public_path($path))) {
                unlink(public_path($path));
                return true;
            }
            return false;
        }
        catch (Exception $e) {
            Log::error('File deletion error: ' . $e->getMessage());
            return false;
        }
    }

    public static function getFileType(string $extension): string
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $videoTypes = ['mp4', 'mov', 'avi'];
        $documentTypes = ['pdf', 'doc', 'docx'];

        $extension = strtolower($extension);
        if (in_array($extension, $imageTypes)) {
            return 'image';
        }
        if (in_array($extension, $videoTypes)) {
            return 'video';
        }
        if (in_array($extension, $documentTypes)) {
            return 'document';
        }
        return 'other';
    }

    private static function formatGroup($days, $hours)
    {
        if (empty($hours['start_time']) || empty($hours['end_time'])) {
            return count($days) > 1 ? "{$days[0]} - {$days[count($days) - 1]}: Closed" : "{$days[0]}: Closed";
        }
        $startTime = date('h:i A', strtotime($hours['start_time']));
        $endTime = date('h:i A', strtotime($hours['end_time']));
        $timeRange = "{$startTime} - {$endTime}";
        return count($days) > 1 ? "{$days[0]} - {$days[count($days) - 1]}: {$timeRange}" : "{$days[0]}: {$timeRange}";
    }


    //! Send firebase notification
    public static function sendCustomerNotifyMobile($token, $data): void
    {
        try {
            // Debugging autoloader
            if (!class_exists(Factory::class)) {
                Log::error('Class "Kreait\\Firebase\\Factory" not found. Check if the Firebase SDK is installed.');
            }
            $factory = (new Factory)->withServiceAccount(storage_path('app/public/customer.json'));

            $messaging    = $factory->createMessaging();
            $notification = FirebaseNotification::create($data['title'], Str::limit($data['message'], 100));

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);
            $messaging->send($message);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

        //! Send firebase notification
    public static function sendRiderNotifyMobile($token, $data): void
    {
        try {
            // Debugging autoloader
            if (!class_exists(Factory::class)) {
                Log::error('Class "Kreait\\Firebase\\Factory" not found. Check if the Firebase SDK is installed.');
            }
            $factory = (new Factory)->withServiceAccount(storage_path('app/public/rider_deliveryman.json'));

            $messaging    = $factory->createMessaging();
            $notification = FirebaseNotification::create($data['title'], Str::limit($data['message'], 100));

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);
            $messaging->send($message);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }
    }


    
    //! check stripe
    public static function checkStripeOnboarding($user, $message): true|\Illuminate\Http\JsonResponse
    {
        if (!$user->onboard_complete)
        {
            $accountID = $user->stripe_account_id;
            if ($accountID) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $stripeAccount = Account::retrieve($accountID,[]);
                if ($stripeAccount && $stripeAccount?->charges_enabled && $stripeAccount?->payouts_enabled){
                    $user->onboard_complete = 1;
                    $user->save();
                    return true;
                } else {
                    return response()->json([
                        'status'  => false,
                        'message' => $message,
                        'data'=>[
                            'onboard_complete' => $user->onboard_complete,
                            'connected_account_url' => null,
                        ],
                        'code'    => 403,
                    ], 403);
                }
            } else {
                return response()->json([
                    'status'  => false,
                    'message' => $message,
                    'data'=>[
                        'onboard_complete' => $user->onboard_complete,
                        'connected_account_url' => null,
                    ],
                    'code'    => 403,
                ], 403);
            }
        }
        return true;
    }


}
