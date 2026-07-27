<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Actions\GeoLocation;
use App\Events\RiderStatusUpdated;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
use App\Models\DrivingInformation;
use App\Models\User;
use App\Models\VehicleInformation;
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

class RiderController extends Controller
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
            $data = User::where('user_type', 'rider')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
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
                $viewButton = ' <a class="btn btn-sm btn-primary" href="' . route('admin.rider.show', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>';

                $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.rider.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';

                $deleteButton = ' <button type="button" onclick="deleteUser(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';

                $accessButton = ' <button type="button" onclick="accessAccount(' . $data->id . ')" class="btn btn-sm btn-success">
                                        <i class="fa-solid fa-envelope"></i>
                                     </button>';

                return '<div>' . $viewButton . ' ' . $editButton . ' ' . $deleteButton . ' ' . $accessButton . '</div>';
            })
                ->rawColumns(['bulk_check', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.rider.v1.index');
    }

    public function create()
    {
        return view('backend.layout.rider.v1.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'name' => 'required|string|max:250',
            'email' => 'required|email|unique:users,email',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'nid' => 'required|string',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|date_format:Y-m-d',
            'vehicle_information_documents' => 'required|array',
            'vehicle_information_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:204800',
            'vehicle_type' => 'required|string',
            'vehicle_model' => 'required|string',
            'vehicle_color' => 'required|string',
            'insurance_number' => 'required|string',
            'manufacture_year' => 'required|string',
            'max_occupancy' => 'required|integer',
            'license_number' => 'required|string',
            'driving_license' => 'required|string',
            'driving_experience' => 'required|string',
            'police_report_documents' => 'required|array',
            'police_report_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:204800',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = new User();
            $user->user_type = 'rider';
            $user->name = $request->name;
            $user->email = $request->email;
            $user->address = $request->address;
            
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            if (empty($latitude) || empty($longitude)) {
                $coords = $this->geoLocation->getCoordinates($request->address);
                $latitude = $coords['latitude'];
                $longitude = $coords['longitude'];
            }

            $user->latitude = $latitude;
            $user->longitude = $longitude;

            $user->phone = $request->phone;
            $user->nid = $request->nid;
            $user->gender = $request->gender;
            $user->date_of_birth = $request->date_of_birth;
            $user->password = Hash::make($request->password);
            $user->status = 'active';
            $user->terms_and_conditions = true;

            if ($request->hasFile('avatar')) {
                $user->avatar = Helper::fileUpload($request->file('avatar'), 'avatar', $request->name);
            }
            $user->save();

            // Vehicle Information
            $vehicle = new VehicleInformation();
            $vehicle->user_id = $user->id;
            $vehicle->vehicle_type = $request->vehicle_type;
            $vehicle->vehicle_model = $request->vehicle_model;
            $vehicle->vehicle_color = $request->vehicle_color;
            $vehicle->insurance_number = $request->insurance_number;
            $vehicle->manufacture_year = $request->manufacture_year;
            $vehicle->max_occupancy = $request->max_occupancy;
            $vehicle->license_number = $request->license_number;

            if ($request->hasFile('vehicle_information_documents')) {
                $vehicle->vehicle_information_documents = Helper::fileUpload($request->file('vehicle_information_documents'), 'vehicle_information_documents', $request->name);
            }
            $vehicle->save();

            // Driving Information
            $driving = new DrivingInformation();
            $driving->user_id = $user->id;
            $driving->driving_license = $request->driving_license;
            $driving->driving_experience = $request->driving_experience;

            if ($request->hasFile('police_report_documents')) {
                $driving->police_report_documents = Helper::fileUpload($request->file('police_report_documents'), 'police_report_documents', $request->name);
            }
            $driving->save();

            flash()->success('Rider created successfully.');
            return redirect()->route('admin.rider.index');
        } catch (Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('backend.layout.rider.v1.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('backend.layout.rider.v1.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'name' => 'sometimes|string|max:250',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'nid' => 'sometimes|string',
            'gender' => 'sometimes|in:male,female,other',
            'date_of_birth' => 'sometimes|date|date_format:Y-m-d',
            'vehicle_information_documents' => 'sometimes|array',
            'vehicle_information_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:204800',
            'vehicle_type' => 'sometimes|string',
            'vehicle_model' => 'sometimes|string',
            'vehicle_color' => 'sometimes|string',
            'insurance_number' => 'sometimes|string',
            'manufacture_year' => 'sometimes|string',
            'max_occupancy' => 'sometimes|integer',
            'license_number' => 'sometimes|string',
            'driving_license' => 'sometimes|string',
            'driving_experience' => 'sometimes|string',
            'police_report_documents' => 'sometimes|array',
            'police_report_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:204800',
        ]);

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->address = $request->address;
            
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            if (empty($latitude) || empty($longitude)) {
                $coords = $this->geoLocation->getCoordinates($request->address);
                $latitude = $coords['latitude'];
                $longitude = $coords['longitude'];
            }

            $user->latitude = $latitude;
            $user->longitude = $longitude;

            $user->phone = $request->phone;
            $user->nid = $request->nid;
            $user->gender = $request->gender;
            $user->date_of_birth = $request->date_of_birth;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->hasFile('avatar')) {
                Helper::deleteFile($user->avatar);
                $user->avatar = Helper::fileUpload($request->file('avatar'), 'avatar', $request->name);
            }
            $user->save();

            // Vehicle Info Update
            $vehicle = $user->vehicleInformation ?? new VehicleInformation(['user_id' => $user->id]);
            $vehicle->vehicle_type = $request->vehicle_type;
            $vehicle->vehicle_model = $request->vehicle_model;
            $vehicle->vehicle_color = $request->vehicle_color;
            $vehicle->insurance_number = $request->insurance_number;
            $vehicle->manufacture_year = $request->manufacture_year;
            $vehicle->max_occupancy = $request->max_occupancy;
            $vehicle->license_number = $request->license_number;

            // Handle Vehicle Documents
            $vehicleDocs = is_array($vehicle->vehicle_information_documents) ? $vehicle->vehicle_information_documents : [];
            if ($request->filled('deleted_vehicle_documents')) {
                foreach ($request->deleted_vehicle_documents as $delPath) {
                    Helper::deleteFile($delPath);
                    $vehicleDocs = array_values(array_diff($vehicleDocs, [$delPath]));
                }
            }
            if ($request->hasFile('vehicle_information_documents')) {
                $newVehicleDocs = Helper::fileUpload($request->file('vehicle_information_documents'), 'vehicle_information_documents', $request->name);
                $vehicleDocs = array_merge($vehicleDocs, $newVehicleDocs);
            }
            $vehicle->vehicle_information_documents = $vehicleDocs;
            $vehicle->save();

            // Driving Info Update
            $driving = $user->drivingInformation ?? new DrivingInformation(['user_id' => $user->id]);
            $driving->driving_license = $request->driving_license;
            $driving->driving_experience = $request->driving_experience;

            // Handle Police Documents
            $policeDocs = is_array($driving->police_report_documents) ? $driving->police_report_documents : [];
            if ($request->filled('deleted_police_report_documents')) {
                foreach ($request->deleted_police_report_documents as $delPath) {
                    Helper::deleteFile($delPath);
                    $policeDocs = array_values(array_diff($policeDocs, [$delPath]));
                }
            }
            if ($request->hasFile('police_report_documents')) {
                $newPoliceDocs = Helper::fileUpload($request->file('police_report_documents'), 'police_report_documents', $request->name);
                $policeDocs = array_merge($policeDocs, $newPoliceDocs);
            }
            $driving->police_report_documents = $policeDocs;
            $driving->save();

            flash()->success('Rider updated successfully.');
            return redirect()->route('admin.rider.index');
        } catch (Exception $e) {
            flash()->error($e->getMessage());
            return back()->withInput();
        }
    }

    public function status(int $id): JsonResponse
    {
        $data = User::findOrFail($id);
        if ($data->status == 'active') {
            $data->status = 'inactive';
            $data->save();

            broadcast(new RiderStatusUpdated($data));

            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
                'data' => $data,
            ]);
        } else {
            $isNewActivation = ($data->status != 'active');
            $data->status = 'active';
            $data->save();

            broadcast(new RiderStatusUpdated($data));
            
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

            broadcast(new RiderStatusUpdated($user));

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
        $deleted = $user->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Account Deleted Successfully'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Account deletion failed.'], 500);
    }

    public function bulkDelete(Request $request)
    {
        try {
            if ($request->ajax()) {
                $users = User::whereIn('id', $request->ids)->get();
                foreach ($users as $user) {
                    if ($user->avatar) {
                        Helper::deleteFile($user->avatar);
                    }
                    $user->delete();
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Riders deleted successfully',
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Invalid request']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
