<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RideController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Ride::with(['user', 'rider'])->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('customer', function ($row) {
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('rider', function ($row) {
                    return $row->rider->name ?? 'Searching...';
                })
                ->addColumn('status', function ($row) {
                    $badges = [
                        'pending' => 'bg-warning',
                        'accepted' => 'bg-info',
                        'started' => 'bg-primary',
                        'completed' => 'bg-success',
                        'canceled' => 'bg-danger'
                    ];
                    $badge = $badges[$row->status] ?? 'bg-secondary';
                    return '<span class="badge ' . $badge . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('admin.rides.show', $row->id) . '" class="btn btn-sm btn-info">View</a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.layout.rides.index');
    }

    public function show($id)
    {
        $ride = Ride::with(['user', 'rider'])->findOrFail($id);
        return view('backend.layout.rides.show', compact('ride'));
    }
}
