<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOption;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Helper\Helper;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Web\Backend\Admin\DeliveryOption\DeliveryOptionStoreRequest;
use App\Http\Requests\Web\Backend\Admin\DeliveryOption\DeliveryOptionUpdateRequest;

class DeliveryOptionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = DeliveryOption::get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    $url = $data->image ? asset($data->image) : asset('backend/assets/images/default-delivery.png');
                    return '<img src="' . $url . '" alt="delivery image" class="rounded" style="width: 50px; height: 50px; object-fit: cover; object-position: center;">';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->is_active;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('is_default', function ($data) {
                    $checked = $data->is_default ? 'checked' : '';
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="setDefault(' . $data->id . ')" type="radio" name="default_option" ' . $checked . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.delivery_options.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';
                    $deleteButton = ' <button type="button" onclick="deleteOption(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                    return '<div>' . $editButton . ' ' . $deleteButton . '</div>';
                })
                ->rawColumns(['image', 'status', 'is_default', 'action'])
                ->make(true);
        }

        return view('backend.layout.delivery_options.index');
    }

    public function create()
    {
        return view('backend.layout.delivery_options.create');
    }

    public function store(DeliveryOptionStoreRequest $request)
    {
        try {
            if ($request->is_default) {
                DeliveryOption::query()->update(['is_default' => false]);
            }

            $option = new DeliveryOption();
            $option->name = $request->name;
            $option->sub_text = $request->sub_text;
            $option->price = $request->price;
            $option->estimated_time = $request->estimated_time;
            $option->is_default = $request->is_default ?? false;
            $option->is_active = $request->is_active ?? true;

            if ($request->hasFile('image')) {
                $option->image = Helper::fileUpload($request->file('image'), 'delivery_option');
            }

            $option->save();

            flash()->success('Delivery Option created successfully.');
            return redirect()->route('admin.delivery_options.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $option = DeliveryOption::findOrFail($id);
        return view('backend.layout.delivery_options.edit', compact('option'));
    }

    public function update(DeliveryOptionUpdateRequest $request, $id)
    {
        $option = DeliveryOption::findOrFail($id);

        try {
            if ($request->is_default) {
                DeliveryOption::query()->update(['is_default' => false]);
            }

            $option->name = $request->name;
            $option->sub_text = $request->sub_text;
            $option->price = $request->price;
            $option->estimated_time = $request->estimated_time;
            $option->is_default = $request->is_default ?? false;
            $option->is_active = $request->is_active ?? $option->is_active;

            if ($request->hasFile('image')) {
                if ($option->image) {
                    Helper::deleteFile($option->image);
                }
                $option->image = Helper::fileUpload($request->file('image'), 'delivery_option');
            }

            $option->save();

            flash()->success('Delivery Option updated successfully.');
            return redirect()->route('admin.delivery_options.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $option = DeliveryOption::find($request->id);
        if ($option && $option->delete()) {
            return response()->json(['success' => true, 'message' => 'Delivery Option deleted successfully'], 200);
        }
        return response()->json(['success' => false, 'message' => 'Deletion failed.'], 500);
    }

    public function status(int $id): JsonResponse
    {
        $data = DeliveryOption::findOrFail($id);
        $data->is_active = !$data->is_active;
        $data->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
            'data' => $data,
        ]);
    }

    public function setDefault(int $id): JsonResponse
    {
        $data = DeliveryOption::findOrFail($id);
        
        // Remove default from all other options
        DeliveryOption::query()->update(['is_default' => false]);

        $data->is_default = true;
        $data->save();

        return response()->json([
            'success' => true,
            'message' => 'Default Delivery Option Updated.',
            'data' => $data,
        ]);
    }
}
