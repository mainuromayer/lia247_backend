<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserDeletionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class TrashUserController extends Controller
{
    /**
     * Display list of all soft-deleted / trash users
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::onlyTrashed()->latest('deleted_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('bulk_check', function ($user) {
                    return Helper::tableCheckbox($user->id);
                })
                ->addColumn('account_id', function ($user) {
                    return '#' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
                })
                ->addColumn('user_type_badge', function ($user) {
                    $type = $user->user_type ?? 'customer';
                    $badgeClass = match ($type) {
                        'customer'     => 'bg-light-primary text-primary',
                        'rider'        => 'bg-light-info text-info',
                        'delivery_man' => 'bg-light-warning text-warning',
                        'vendor'       => 'bg-light-success text-success',
                        default        => 'bg-light-secondary text-secondary',
                    };

                    $label = strtoupper(str_replace('_', ' ', $type));
                    return '<span class="badge ' . $badgeClass . ' px-2 py-1">' . e($label) . '</span>';
                })
                ->addColumn('original_email_display', function ($user) {
                    if (!empty($user->original_email)) {
                        return '<span class="badge bg-light-primary text-primary font-weight-bold">' . e($user->original_email) . '</span>';
                    }
                    return '<span class="text-muted small">Not Recorded</span>';
                })
                ->addColumn('deleted_date', function ($user) {
                    return $user->deleted_at ? $user->deleted_at->format('M d, Y h:i A') : 'N/A';
                })
                ->addColumn('action', function ($user) {
                    $restoreBtn = '<button type="button" onclick="restoreUser(' . $user->id . ')" class="btn btn-sm btn-success me-1" title="Restore User">
                                        <i class="fa-solid fa-rotate-left"></i> Restore
                                   </button>';

                    $deleteBtn = '<button type="button" onclick="forceDeleteUser(' . $user->id . ')" class="btn btn-sm btn-danger" title="Permanently Delete">
                                        <i class="fa-solid fa-trash"></i> Permanent Delete
                                  </button>';

                    return '<div class="d-flex align-items-center gap-1">' . $restoreBtn . ' ' . $deleteBtn . '</div>';
                })
                ->rawColumns(['bulk_check', 'user_type_badge', 'original_email_display', 'action'])
                ->make(true);
        }

        $trashCount = User::onlyTrashed()->count();
        return view('backend.layout.trash_user.index', compact('trashCount'));
    }

    /**
     * Restore a soft-deleted user
     */
    public function restore(Request $request, $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found in trash.',
            ], 404);
        }

        $result = UserDeletionService::restoreUser($user);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Permanently delete a user from database
     */
    public function forceDelete(Request $request, $id): JsonResponse
    {
        // Require admin password verification for security
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect admin password.',
            ], 401);
        }

        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found in trash.',
            ], 404);
        }

        UserDeletionService::forceDeleteUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User account permanently purged from database.',
        ], 200);
    }

    /**
     * Bulk restore multiple users
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->ids;
        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No users selected.',
            ], 400);
        }

        $users = User::onlyTrashed()->whereIn('id', $ids)->get();
        $restoredCount = 0;

        foreach ($users as $user) {
            $result = UserDeletionService::restoreUser($user);
            if ($result['success']) {
                $restoredCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$restoredCount} users successfully restored.",
        ], 200);
    }

    /**
     * Bulk permanent delete
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect admin password.',
            ], 401);
        }

        $ids = $request->ids;
        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No users selected.',
            ], 400);
        }

        $users = User::onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($users as $user) {
            UserDeletionService::forceDeleteUser($user);
        }

        return response()->json([
            'success' => true,
            'message' => count($users) . ' users permanently deleted.',
        ], 200);
    }
}
