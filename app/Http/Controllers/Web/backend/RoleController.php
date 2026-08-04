<?php

namespace App\Http\Controllers\Web\backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Exception;
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::whereIn('name', ['admin', 'sub_admin'])->orWhere('guard_name', 'web')->whereNotIn('name', ['customer', 'vendor', 'rider', 'delivery_man'])->get();
        return view('backend.layout.role_permissions.role.index', get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::get();
        return view('backend.layout.role_permissions.role.create', get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        if (strtolower($request->name) === 'admin' || strtolower($request->name) === 'super admin' || strtolower($request->name) === 'super_admin') {
            return redirect()->back()->with('error', 'Super Admin is a pre-defined system role and cannot be recreated.')->withInput();
        }

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.role.list')->with('success', 'Role Created Successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $permissions = Permission::get();
        $role = Role::findOrFail($id);
        $rolePermissions = DB::table('role_has_permissions')
            ->where('role_has_permissions.role_id', $role->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
        return view('backend.layout.role_permissions.role.add_permissions', get_defined_vars());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
        ]);
        $role = Role::findOrFail($id);
        $role->syncPermissions($request->permissions ?? []);
        return redirect()->route('admin.role.list')->with('success', 'Permissions Updated Successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found.',
                ], 404);
            }

            if (in_array(strtolower($role->name), ['admin', 'super admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super Admin is a pre-defined system role and cannot be deleted.',
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the role. Please try again.',
            ], 500);
        }
    }
}
