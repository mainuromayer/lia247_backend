<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
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

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('user_type', 'customer')->latest();
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
                $viewButton = ' <a class="btn btn-sm btn-primary" href="' . route('admin.customer.show', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>';

                $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.customer.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';

                $deleteButton = ' <button type="button" onclick="deleteUser(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';

                return '<div>' . $viewButton . ' ' . $editButton . ' ' . $deleteButton . '</div>';
            })
                ->rawColumns(['bulk_check', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.customer.v1.index');
    }

    public function create()
    {
        return view('backend.layout.customer.v1.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            $user = new User();
            $user->user_type = 'customer';
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->status = 'active';
            $user->save();

            flash()->success('Customer created successfully.');
            return redirect()->route('admin.customer.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('backend.layout.customer.v1.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('backend.layout.customer.v1.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|unique:users,phone,' . $id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            flash()->success('Customer updated successfully.');
            return redirect()->route('admin.customer.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
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
        } else {
            $isNewActivation = ($data->status != 'active');
            $data->status = 'active';
            $data->save();
            
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
                'message' => 'Customers deleted successfully',
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Something went wrong']);
    }
}
