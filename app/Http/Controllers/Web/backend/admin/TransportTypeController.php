<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\TransportType;
use App\Models\TransportTypeCategory;
use Illuminate\Http\Request;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;

class TransportTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TransportType::with('category');

            if ($request->filled('category_id')) {
                $query->where('transport_type_category_id', $request->category_id);
            }

            $data = $query->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('category', function ($data) {
                    return $data->category->name ?? 'N/A';
                })
                ->addColumn('icon', function ($data) {
                    $url = $data->icon ? asset($data->icon) : asset('backend/assets/images/default-transport.png');
                    return '<img src="' . $url . '" alt="icon" class="rounded" width="50" height="50">';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->is_active;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.transport_types.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';
                    $deleteButton = ' <button type="button" onclick="deleteTransportType(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                    return '<div class="d-flex">' . $editButton . ' ' . $deleteButton . '</div>';
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        $categories = TransportTypeCategory::where('status', true)->get();
        return view('backend.admin.transport_type.index', compact('categories'));
    }

    public function create()
    {
        $categories = TransportTypeCategory::where('status', true)->get();
        return view('backend.admin.transport_type.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transport_type_category_id' => 'required|exists:transport_type_categories,id',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price_per_km' => 'required|numeric|min:0',
            'min_balance' => 'required|numeric|min:0',
        ]);

        try {
            $data = $request->except('icon');
            if ($request->hasFile('icon')) {
                $data['icon'] = Helper::fileUpload($request->file('icon'), 'transport_type', $request->name);
            }
            TransportType::create($data);
            flash()->success('Transport Type created successfully.');
            return redirect()->route('admin.transport_types.index');
        } catch (Exception $e) {
            flash()->error('Failed to create transport type: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $transportType = TransportType::findOrFail($id);
        $categories = TransportTypeCategory::where('status', true)->get();
        return view('backend.admin.transport_type.edit', compact('transportType', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transport_type_category_id' => 'required|exists:transport_type_categories,id',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price_per_km' => 'required|numeric|min:0',
            'min_balance' => 'required|numeric|min:0',
        ]);

        try {
            $transportType = TransportType::findOrFail($id);
            $data = $request->except('icon');
            if ($request->hasFile('icon')) {
                Helper::deleteFile($transportType->icon);
                $data['icon'] = Helper::fileUpload($request->file('icon'), 'transport_type', $request->name);
            }
            $transportType->update($data);
            flash()->success('Transport Type updated successfully.');
            return redirect()->route('admin.transport_types.index');
        } catch (Exception $e) {
            flash()->error('Failed to update transport type: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $transportType = TransportType::findOrFail($request->id);
            Helper::deleteFile($transportType->icon);
            $transportType->delete();
            return response()->json(['success' => true, 'message' => 'Transport Type deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete transport type']);
        }
    }

    public function status(int $id): JsonResponse
    {
        try {
            $transportType = TransportType::findOrFail($id);
            $transportType->is_active = !$transportType->is_active;
            $transportType->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
