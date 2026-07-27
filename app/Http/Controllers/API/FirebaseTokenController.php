<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\FirebaseToken\FirebaseResource;
use App\Models\FirebaseToken;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FirebaseTokenController extends Controller
{
    use ApiResponse;

    /**
     * Store Firebase Token
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'token' => 'required|string',
            'device_id' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }
        try {
            $user = FirebaseToken::where('user_id', $request->user_id)->where('device_id', $request->device_id)->first();
            if ($user) {
                $user->delete();
            }

            $data = new FirebaseToken();
            $data->user_id = $request->user_id;
            $data->token = $request->token;
            $data->device_id = $request->device_id;
            $data->is_active = $request->is_active ?? 1;
            $data->save();

            return $this->success(new FirebaseResource($data), 'Token saved successfully');
        } catch (Exception $e) {
            return $this->error([], 'An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Single Record
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'device_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->error([], 'An error occurred: ' . $validator->errors()->first(), 400);
        }
        $user_id = $request->user_id;
        $device_id = $request->device_id;
        $data = FirebaseToken::where('user_id', $user_id)->where('device_id', $device_id)->first();
        if (!$data) {
            return $this->error([], 'No records found', 404);
        }
        return $this->success(new FirebaseResource($data), 'Token fetched successfully');
    }

    /**
     * Delete Token Single Record
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'device_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $user = FirebaseToken::where('user_id', $request->user_id)->where('device_id', $request->device_id)->first();
        if ($user) {
            $user->delete();
            return $this->success([], 'Token deleted successfully');
        } else {
            return $this->error([], 'No records found', 404);
        }
    }
}