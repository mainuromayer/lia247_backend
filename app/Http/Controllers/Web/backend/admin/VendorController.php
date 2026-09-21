<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Actions\GeoLocation;
use App\Events\VendorStatusUpdated;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
use App\Models\Business;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VendorController extends Controller
{
    use ApiResponse;

    protected $geoLocation;

    public function __construct(GeoLocation $geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('user_type', 'vendor')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowClass(function ($data) {
                    return $data->status !== 'active' ? 'table-warning' : '';
                })
                ->addColumn('status', function ($data) {
                $status = $data->status;
                return '<div class="form-check form-switch mb-2">
                            <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status == 'active' ? 'checked' : '') . '>
                        </div>';
            })
                ->addColumn('bulk_check', function ($data) {
                return Helper::tableCheckbox($data->id);
            })
                ->addColumn('action', function ($data) {
                $viewButton = ' <a class="btn btn-sm btn-primary" href="' . route('admin.vendor.show', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>';

                $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.vendor.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';

                $deleteButton = ' <button type="button" onclick="deleteUser(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                
                $accessButton = ' <button type="button" onclick="accessAccount(' . $data->id . ')" class="btn btn-sm btn-success" title="Access Account">
                                        <i class="fa-solid fa-sign-in-alt"></i>
                                     </button>';

                return '<div>' . $viewButton . ' ' . $editButton . ' ' . $deleteButton . ' ' . $accessButton . '</div>';
            })
                ->rawColumns(['bulk_check', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.vendor.v1.index');
    }

    public function create()
    {
        return view('backend.layout.vendor.v1.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',

            // business info
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'business_name' => 'required|string|max:255',
            'business_description' => 'nullable|string',
            'business_phone' => 'required|string|max:20',
            'business_address' => 'required|string',
            'opening_time' => 'required|string|date_format:H:i',
            'closing_time' => 'required|string|date_format:H:i',
            'license_number' => 'required|string|max:100',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'free_delivery' => 'nullable|boolean',
            'free_delivery_type' => 'nullable|in:admin,vendor,hybrid',
            'admin_contribution' => 'nullable|numeric|min:0',
            'vendor_contribution' => 'nullable|numeric|min:0',
        ]);



        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->status = 'active';
            $user->user_type = 'vendor';

            if ($request->hasFile('avatar')) {
                $user->avatar = Helper::fileUpload($request->file('avatar'), 'avatar', $request->name);
            }

            $user->save();

            $business = new Business();
            $business->user_id = $user->id;
            $business->business_name = $request->business_name;
            $business->business_description = $request->business_description;
            $business->business_phone = $request->business_phone;
            $business->business_address = $request->business_address;
            $business->license_number = $request->license_number;
            $business->opening_time = $request->opening_time;
            $business->closing_time = $request->closing_time;
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            if (empty($latitude) || empty($longitude)) {
                $coords = $this->geoLocation->getCoordinates($request->business_address);
                $latitude = $coords['latitude'];
                $longitude = $coords['longitude'];
            }

            $business->latitude = $latitude;
            $business->longitude = $longitude;

            if ($request->hasFile('cover_photo')) {
                $business->cover_photo = Helper::fileUpload($request->file('cover_photo'), 'business/cover', $request->business_name);
            }

            $business->free_delivery = $request->free_delivery ?? false;
            $business->free_delivery_type = $request->free_delivery_type;
            $business->admin_contribution = $request->admin_contribution ?? 0;
            $business->vendor_contribution = $request->vendor_contribution ?? 0;

            $business->save();

            DB::commit();
            flash()->success('Vendor created successfully.');
            return redirect()->route('admin.vendor.index');
        } catch (Exception $e) {
            DB::rollback();
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $user = User::with('business')->findOrFail($id);
        return view('backend.layout.vendor.v1.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::with('business')->findOrFail($id);
        return view('backend.layout.vendor.v1.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',

            // business info
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'business_name' => 'required|string|max:255',
            'business_description' => 'nullable|string',
            'business_phone' => 'required|string|max:20',
            'business_address' => 'required|string',
            'opening_time' => 'required|string|date_format:H:i',
            'closing_time' => 'required|string|date_format:H:i',
            'license_number' => 'required|string|max:100',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'free_delivery' => 'nullable|boolean',
            'free_delivery_type' => 'nullable|in:admin,vendor,hybrid',
            'admin_contribution' => 'nullable|numeric|min:0',
            'vendor_contribution' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $user->name = $request->name;
            $user->email = $request->email;
            // $user->phone = $request->phone; // If phone is needed on user table, uncomment and ensure it's in form
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('avatar')) {
                Helper::deleteFile($user->avatar);
                $user->avatar = Helper::fileUpload($request->file('avatar'), 'avatar', $request->name);
            }

            $user->save();

            $business = Business::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $request->business_name,
                    'business_description' => $request->business_description,
                    'business_phone' => $request->business_phone,
                    'business_address' => $request->business_address,
                    'license_number' => $request->license_number,
                    'opening_time' => $request->opening_time,
                    'closing_time' => $request->closing_time,
                    'latitude' => !empty($request->latitude) ? $request->latitude : $this->geoLocation->getCoordinates($request->business_address)['latitude'],
                    'longitude' => !empty($request->longitude) ? $request->longitude : $this->geoLocation->getCoordinates($request->business_address)['longitude'],
                    'free_delivery' => $request->free_delivery ?? false,
                    'free_delivery_type' => $request->free_delivery_type,
                    'admin_contribution' => $request->admin_contribution ?? 0,
                    'vendor_contribution' => $request->vendor_contribution ?? 0,
                ]
            );

            if ($request->hasFile('cover_photo')) {
                Helper::deleteFile($business->cover_photo);
                $business->cover_photo = Helper::fileUpload($request->file('cover_photo'), 'business/cover', $request->business_name);
                $business->save();
            }

            DB::commit();
            flash()->success('Vendor updated successfully.');
            return redirect()->route('admin.vendor.index');
        } catch (Exception $e) {
            DB::rollback();
            flash()->error('Something went wrong! ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function status(int $id): JsonResponse
    {
        $data = User::findOrFail($id);
        if ($data->status == 'active') {
            $data->status = 'inactive';
            $data->save();

            broadcast(new VendorStatusUpdated($data));

            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
                'data' => $data,
            ]);
        } else {
            $isNewActivation = ($data->status != 'active');
            $data->status = 'active';
            $data->save();

            broadcast(new VendorStatusUpdated($data));
            
            if ($isNewActivation) {
                try {
                     Mail::to($data->email)->send(new AccountActivationMail($data));
                } catch (Exception $e) {
                     Log::error('Mail Send Error: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => $isNewActivation ? 'Published and Activation Email Sent.' : 'Published Successfully.',
                'data' => $data,
            ]);
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->status = 'active';
            $user->save();

            broadcast(new VendorStatusUpdated($user));

            Mail::to($user->email)->send(new AccountActivationMail($user));

            return response()->json([
                'success' => true,
                'message' => 'Account activated and email sent successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Activation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate or send email.',
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect Password'], 401);
        }

        $user = User::find($request->id);
        if ($user->avatar) {
            Helper::deleteFile($user->avatar);
        }
        $business = Business::where('user_id', $user->id)->first();
        if ($business && $business->cover_photo) {
            Helper::deleteFile($business->cover_photo);
        }

        $deleted = $user->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Account Deleted Successfully'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Account deletion failed.'], 500);
    }

    public function bulkDelete(Request $request)
    {
        if ($request->ajax()) {
            $users = User::whereIn('id', $request->ids)->get();
            foreach ($users as $user) {
                if ($user->avatar) {
                    Helper::deleteFile($user->avatar);
                }
                $business = Business::where('user_id', $user->id)->first();
                if ($business && $business->cover_photo) {
                    Helper::deleteFile($business->cover_photo);
                }
                $user->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'Vendors deleted successfully',
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Something went wrong']);
    }
}
