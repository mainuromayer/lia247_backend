<?php

namespace App\Http\Controllers\Web\backend;

use App\Models\User;
use App\Helper\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\UserService;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponse;
    public $userServiceObj;

    public function __construct()
    {
        $this->userServiceObj = new UserService();
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('is_admin', false)->where('id', '!=', Auth::id())->latest();
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
                $editButton = '';
                if (Auth::user()->is_admin) {
                    $editRoute = route('user.edit', ['id' => $data->id]);
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . $editRoute . '">
                                        <i class="fa-solid fa-pencil"></i>
                                    </a>';
                }

                $viewButton = '';
                if (Auth::user()->is_admin) {
                    $viewRoute = route('show.user', ['id' => $data->id]);
                    $viewButton = ' <a class="btn btn-sm btn-primary" href="' . $viewRoute . '">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>';
                }

                $deleteButton = '';
                if (Auth::user()->is_admin) {
                    $deleteButton = '<button type="button" onclick="deleteUser(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                }

                return '<div>
                            ' . $editButton . '
                            ' . $viewButton . '
                            ' . $deleteButton . '
                        </div>';
            })

                ->rawColumns(['bulk_check', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.user.index');
    }

    public function create()
    {
        $data['roles'] = Role::all();
        return view('backend.layout.user.create', $data);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'role' => 'required',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'required',
            'password' => 'required|string|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }
        $data = $request->all();
        // Create a new user instance
        $user = new User();
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->phone = $data['phone'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']); // Make sure password is hashed

        $user->save();

        $user->assignRole($data['role']);

        // Redirect or return a success message
        return redirect()->route('user.list')->with('success', 'User created successfully!');
    }

    public function edit($id)
    {
        $data['user'] = User::find($id);
        $data['role'] = User::find($id)->role;

        return view('backend.layout.user.edit', $data);
    }

    public function update(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            $rules = [
                'first_name' => 'sometimes|required|string|max:250',
                'last_name' => 'sometimes|required|string|max:250',
                'email' => "sometimes|required|email|unique:users,email,{$user->id}",
                'phone' => 'sometimes|required|string|max:15',
                'avatar' => 'nullable|image|mimes:jpg,png,jpeg|max:204800',
                'avatar_cropped' => 'nullable|string',
                'password' => 'nullable|min:8|confirmed',
            ];

            $validated = $request->validate($rules);

            // Update user data
            $user->update([
                'first_name' => $validated['first_name'] ?? $user->first_name,
                'last_name' => $validated['last_name'] ?? $user->last_name,
                'email' => $validated['email'] ?? $user->email,
                'phone' => $validated['phone'] ?? $user->phone,
                'password' => isset($validated['password']) ? bcrypt($validated['password']) : $user->password,
            ]);

            // Handle avatar upload
            if ($request->has('avatar_cropped') && !empty($request->avatar_cropped)) {
                // Process cropped image (base64 data)
                $croppedImage = $request->avatar_cropped;
                if (preg_match('/^data:image\/(\w+);base64,/', $croppedImage, $type)) {
                    $data = substr($croppedImage, strpos($croppedImage, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, jpeg
                    if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                        throw new \Exception('Invalid image format');
                    }
                    $data = base64_decode($data);
                    if ($data === false) {
                        throw new \Exception('Base64 decode failed');
                    }

                    // Create directory if it doesn't exist
                    $directory = public_path('uploads/profile_pictures/admins');
                    if (!File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }

                    // Generate filename
                    $filename = 'uploads/profile_pictures/admins/' . Str::slug($user->name) . '-' . uniqid() . '.' . $type;
                    $path = public_path($filename);

                    // Save the cropped image
                    if (file_put_contents($path, $data) === false) {
                        Log::error('Failed to save cropped image to ' . $path);
                        throw new \Exception('Failed to save cropped image');
                    }

                    // Delete old avatar if exists
                    if ($user->avatar && file_exists(public_path($user->avatar)) && $user->avatar != 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg') {
                        unlink(public_path($user->avatar));
                    }

                    $user->update(['avatar' => $filename]);
                }
            }
            elseif ($request->hasFile('avatar')) {
                // Handle regular file upload
                if ($user->avatar && file_exists(public_path($user->avatar)) && $user->avatar != 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg') {
                    unlink(public_path($user->avatar));
                }

                // Create directory if it doesn't exist
                $directory = public_path('uploads/profile_pictures/admins');
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                $path = Helper::fileUpload($request->file('avatar'), 'profile_pictures/admins', $user->name);
                if (!$path) {
                    Log::error('Helper::fileUpload failed for regular avatar upload');
                    throw new \Exception('Failed to upload avatar');
                }

                $user->update(['avatar' => $path]);
            }

            return redirect()->back()->with('success', 'Information Updated');
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator->errors())->withInput();
        }
        catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        return $this->userServiceObj->show($id);
    }

    public function status(int $id): JsonResponse
    {
        $data = User::findOrFail($id);
        if ($data->status == 'active') {
            $data->status = 'inactive';
            $data->save();

            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
                'data' => $data,
            ]);
        }
        else {
            $data->status = 'active';
            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Published Successfully.',
                'data' => $data,
            ]);
        }
    }

    public function destroy(Request $request)
    {
        if (!Hash::check($request->password, Auth::user()->password)) {
            return $this->error([], 'Incorrect Password', 401);
        }

        $user = User::find($request->id);
        $deleted = $user->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        if ($deleted) {
            return $this->success([], 'Account Deleted Successfully', 200);
        }

        return $this->error([], 'Account deletion failed. Please try again.', 500);
    }
}
