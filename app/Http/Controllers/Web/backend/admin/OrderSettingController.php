<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use App\Http\Requests\Web\Backend\Admin\OrderSetting\OrderSettingUpdateRequest;
use Exception;

class OrderSettingController extends Controller
{
    public function index()
    {
        $setting = SystemSetting::first();
        return view('backend.layout.order_settings.index', compact('setting'));
    }

    public function update(OrderSettingUpdateRequest $request)
    {
        try {
            $setting = SystemSetting::first();
            if (!$setting) {
                $setting = new SystemSetting();
            }
            
            $data = $request->validated();
            $setting->fill($data);
            
            $setting->save();

            flash()->success('Order settings updated successfully.');
            return redirect()->back();
        } catch (Exception $e) {
            flash()->error('Something went wrong!');
            return redirect()->back();
        }
    }
}
