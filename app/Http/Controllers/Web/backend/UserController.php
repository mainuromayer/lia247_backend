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
            $data = User::where('id', '!=', Auth::id())
                ->where(function($query) {
                    $query->where('user_type', 'sub_admin')
                          ->orWhereHas('roles', function($q) {
                              $q->where('name', '!=', 'admin');
                          });
                })->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($data) {
                    return $data->name ?? ($data->first_name . ' ' . $data->last_name);
                })
                ->addColumn('role', function ($data) {
                    $roles = $data->getRoleNames()->toArray();
                    if (count($roles) > 0) {
                        return implode(', ', array_map(function($r) {
                            return ucwords(str_replace('_', ' ', $r));
                        }, $roles));
                    }
                    return 'Sub Admin';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->status;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status == 'active' ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {
                    $editRoute = route('admin.sub_admin.edit', ['id' => $data->id]);
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . $editRoute . '">
                                        <i class="fa fa-pencil"></i>
                                    </a>';

                    $deleteButton = '<button type="button" onclick="deleteSubAdmin(' . $data->id . ')" class="btn btn-sm btn-danger ml-1">
                                        <i class="fa fa-trash"></i>
                                     </button>';

                    return '<div>
                                ' . $editButton . '
                                ' . $deleteButton . '
                            </div>';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.layout.user.index');
    }

    public function create()
    {
        $data['roles'] = Role::whereIn('name', ['admin', 'sub_admin'])->orWhere('guard_name', 'web')->whereNotIn('name', ['customer', 'vendor', 'rider', 'delivery_man'])->get();
        return view('backend.layout.user.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'nullable|string',
            'password' => 'required|string|confirmed|min:6',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->user_type = ($request->role === 'admin') ? 'admin' : 'sub_admin';
        $user->is_admin = ($request->role === 'admin');
        $user->status = 'active';
        $user->password = Hash::make($request->password);
        $user->save();

        $user->assignRole($request->role);

        return redirect()->route('admin.sub_admin.index')->with('success', 'Sub Admin created successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::whereIn('name', ['admin', 'sub_admin'])->orWhere('guard_name', 'web')->whereNotIn('name', ['customer', 'vendor', 'rider', 'delivery_man'])->get();
        $userRole = $user->roles->first();

        return view('backend.layout.user.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id = null)
    {
        $userId = $id ?? $request->id;
        $user = User::findOrFail($userId);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone' => 'nullable|string',
            'password' => 'nullable|min:6',
            'role' => 'required',
        ];

        $request->validate($rules);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
            $user->user_type = ($request->role === 'admin') ? 'admin' : 'sub_admin';
            $user->is_admin = ($request->role === 'admin');
            $user->save();
        }

        return redirect()->route('admin.sub_admin.index')->with('success', 'Sub Admin updated successfully!');
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
