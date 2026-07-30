<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Traits\ApiResponse;
use App\Jobs\SendAdPromotionEmailJob;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class AdController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Ad::latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    return '<img src="' . $data->image . '" style="width: 50px; height: 50px; object-fit: cover;">';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->is_active;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {


                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.ads.edit', ['id' => $data->id]) . '">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>';

                    $deleteButton = ' <button type="button" onclick="deleteAd(' . $data->id . ')" class="btn btn-sm btn-danger">
                                            <i class="fa-regular fa-circle-xmark"></i>
                                         </button>';

                    $sendEmailButton = ' <button type="button" onclick="sendPromoEmail(' . $data->id . ')" class="btn btn-sm btn-success" title="Send Promotion to Customers">
                                            <i class="fa-solid fa-envelope"></i>
                                         </button>';

                    return '<div>' . $deleteButton . ' ' . $editButton . ' ' . $sendEmailButton . '</div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.ads.index');
    }

    public function create()
    {
        return view('backend.layout.ads.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:20480',
            'url' => 'nullable|url',
            'is_active' => 'required|in:0,1',
        ]);

        try {
            $ad = new Ad();
            $ad->url = $request->url;
            $ad->is_active = $request->is_active;

            if ($request->hasFile('image')) {
                $ad->image = Helper::fileUpload($request->file('image'), 'ads/images', 'ad-img-' . time());
            }

            if ($request->hasFile('video')) {
                $ad->video = Helper::fileUpload($request->file('video'), 'ads/videos', 'ad-vid-' . time());
            }

            $ad->save();

            flash()->success('Ad created successfully.');
            return redirect()->route('admin.ads.index');
        } catch (Exception $e) {
            Log::error('Ad Store Error: ' . $e->getMessage());
            flash()->error('Something went wrong!');
            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $ad = Ad::findOrFail($id);
        return view('backend.layout.ads.edit', compact('ad'));
    }

    public function update(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:20480',
            'url' => 'nullable|url',
            'is_active' => 'required|in:0,1',
        ]);

        try {
            $ad->url = $request->url;
            $ad->is_active = $request->is_active;

            if ($request->hasFile('image')) {
                Helper::deleteFile($ad->getRawOriginal('image'));
                $ad->image = Helper::fileUpload($request->file('image'), 'ads/images', 'ad-img-' . time());
            }

            if ($request->hasFile('video')) {
                Helper::deleteFile($ad->getRawOriginal('video'));
                $ad->video = Helper::fileUpload($request->file('video'), 'ads/videos', 'ad-vid-' . time());
            }

            $ad->save();

            flash()->success('Ad updated successfully.');
            return redirect()->route('admin.ads.index');
        } catch (Exception $e) {
            Log::error('Ad Update Error: ' . $e->getMessage());
            flash()->error('Something went wrong!');
            return back()->withInput();
        }
    }

    public function status(int $id): JsonResponse
    {
        $ad = Ad::findOrFail($id);
        $ad->is_active = !$ad->is_active;
        $ad->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
            'data' => $ad,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $ad = Ad::findOrFail($id);
            Helper::deleteFile($ad->getRawOriginal('image'));
            Helper::deleteFile($ad->getRawOriginal('video'));
            $ad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ad Deleted Successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed.',
            ], 500);
        }
    }

    public function sendMail(int $id): JsonResponse
    {
        try {
            $ad = Ad::findOrFail($id);

            SendAdPromotionEmailJob::dispatch($ad, request()->getSchemeAndHttpHost());

            return response()->json([
                'success' => true,
                'message' => 'Promotion emails are being sent to all active customers.',
            ]);
        } catch (Exception $e) {
            Log::error('Ad Send Mail Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch promotion emails: ' . $e->getMessage(),
            ], 500);
        }
    }
}
