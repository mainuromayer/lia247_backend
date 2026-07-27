<?php

namespace App\Http\Controllers\Web\backend;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Service;
use App\Services\SettingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingController extends Service
{
    public $settingServiceObj;

    public function __construct()
    {
        $this->settingServiceObj = new SettingService();
    }

    public function adminSetting()
    {
        return $this->settingServiceObj->adminSettingPage();
    }

    public function adminSettingUpdate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'admin_title' => 'required|string|max:150',
            'admin_short_title' => 'nullable|string|max:100',
            'admin_copyright_text' => 'nullable|string|max:500',
        ], [
            'admin_title.required' => 'The admin title is required.',
            'admin_title.max' => 'The admin title must not exceed 150 characters.',
            'admin_short_title.max' => 'The admin short title must not exceed 100 characters.',
            'admin_copyright_text.max' => 'The copyright text must not exceed 500 characters.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $setting = SystemSetting::first();
            if (!$setting) {
                $setting = new SystemSetting();
            }

            $data = $request->except(['admin_logo', 'admin_favicon']);
            $data['admin_title'] = Str::title($request->admin_title);

            if ($request->hasFile('admin_logo')) {
                if ($setting->admin_logo && file_exists(public_path($setting->admin_logo)) && $setting->admin_logo != 'https://tuto-photos.com/wp-content/plugins/sfwd-lms/assets/images/nologo.jpg') {
                    unlink(public_path($setting->admin_logo));
                }
                $path = $this->fileUpload($request->admin_logo, 'systems/logo/');
                $data['admin_logo'] = $path;
            }

            if ($request->hasFile('admin_favicon')) {
                if ($setting->admin_favicon && file_exists(public_path($setting->admin_favicon)) && $setting->admin_favicon != 'uploads/systems/favicon/favico.png') {
                    unlink(public_path($setting->admin_favicon));
                }
                $path = $this->fileUpload($request->admin_favicon, 'systems/favicon/');
                $data['admin_favicon'] = $path;
            }

            $setting->fill($data);
            $setting->save();

            return redirect()->back()->with('success', 'Update information successfully');

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function systemSetting()
    {
        $data['setting'] = SystemSetting::first();

        return view('backend.layout.setting.system-setting')->with($data);
    }

    public function systemSettingUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'system_title' => 'required|string|max:150',
            'system_short_title' => 'nullable|string|max:100',
            'tag_line' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:150',
            'phone_code' => 'required|string|max:5',
            'phone_number' => 'required|string|max:15|regex:/^\d+$/',
            'email' => 'required|email|max:150',
            'copyright' => 'nullable|string|max:500',
        ], [
            'system_title.required' => 'The system title is required.',
            'system_title.max' => 'The system title must not exceed 150 characters.',
            'company_name.required' => 'The company name is required.',
            'phone_code.required' => 'The phone code is required.',
            'phone_number.required' => 'The phone number is required.',
            'phone_number.regex' => 'The phone number must contain only digits.',
            'email.required' => 'The email is required.',
            'email.email' => 'Enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $setting = SystemSetting::first();
            if (!$setting) {
                $setting = new SystemSetting();
            }

            $data = $request->except(['logo', 'favicon']);
            $data['system_title'] = Str::title($request->system_title);
            if ($request->hasFile('logo')) {
                if ($setting->logo && file_exists(public_path($setting->logo)) && $setting->logo != 'https://tuto-photos.com/wp-content/plugins/sfwd-lms/assets/images/nologo.jpg') {
                    unlink(public_path($setting->logo));
                }
                $path = $this->fileUpload($request->logo, 'systems/logo/');
                $data['logo'] = $path;
            }

            if ($request->hasFile('favicon')) {
                if ($setting->favicon && file_exists(public_path($setting->favicon)) && $setting->favicon != 'uploads/systems/favicon/favico.png') {
                    unlink(public_path($setting->favicon));
                }
                $path = $this->fileUpload($request->favicon, 'systems/favicon/');
                $data['favicon'] = $path;
            }

            $setting->fill($data);
            $setting->save();

            return redirect()->back()->with('success', 'Update information successfully');

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function mail()
    {
        return view('backend.layout.setting.mail');
    }

    public function leadPrice()
    {
        // Get the first record's lead_price, or default to 60.00
        $lead_price = SystemSetting::value('lead_price') ?? 60.00;

        return view('backend.layout.setting.lead_price', compact('lead_price'));
    }


    public function leadPriceUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $setting = SystemSetting::firstOrNew(); // create new if not exists
            $setting->lead_price = $request->lead_price;
            $setting->save();

            return redirect()->back()->with('success', 'Lead price updated successfully');

        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function platformFee()
    {
        // Get the first record's platform_fee, or default to 20.00
        $platform_fee = SystemSetting::value('platform_fee') ?? 20.00;

        return view('backend.layout.setting.platform_fee', compact('platform_fee'));
    }

    public function platformFeeUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform_fee' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $setting = SystemSetting::firstOrNew(); // create new if not exists
            $setting->platform_fee = $request->platform_fee;
            $setting->save();

            return redirect()->back()->with('success', 'Platform Fee updated successfully');

        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function mailstore(Request $request)
    {
        $request->validate([
            'mail_mailer' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|string',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|string',
        ]);
        try {
            $envContent = File::get(base_path('.env'));
            $lineBreak = "\n";
            $envContent = preg_replace([
                '/MAIL_MAILER=(.*)\s/',
                '/MAIL_HOST=(.*)\s/',
                '/MAIL_PORT=(.*)\s/',
                '/MAIL_USERNAME=(.*)\s/',
                '/MAIL_PASSWORD=(.*)\s/',
                '/MAIL_ENCRYPTION=(.*)\s/',
                '/MAIL_FROM_ADDRESS=(.*)\s/',
            ], [
                'MAIL_MAILER=' . $request->mail_mailer . $lineBreak,
                'MAIL_HOST=' . $request->mail_host . $lineBreak,
                'MAIL_PORT=' . $request->mail_port . $lineBreak,
                'MAIL_USERNAME=' . $request->mail_username . $lineBreak,
                'MAIL_PASSWORD=' . $request->mail_password . $lineBreak,
                'MAIL_ENCRYPTION=' . $request->mail_encryption . $lineBreak,
                'MAIL_FROM_ADDRESS=' . '"' . $request->mail_from_address . '"' . $lineBreak,
            ], $envContent);

            if ($envContent !== null) {
                File::put(base_path('.env'), $envContent);
            }
            return back()->with('success', 'Updated successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update');
        }

        return redirect()->back();
    }

    public function integrationSetting()
    {
        return view('backend.layout.setting.integration');
    }

    public function integrationSettingStore(Request $request)
    {
        $request->validate([
            'frontend_url' => 'required|url',
            'google_maps_api_key' => 'nullable|string',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
            'cashier_currency' => 'required|string|max:10',
            'webhook_redirect_url' => 'nullable|string',
            'reverb_app_id' => 'nullable|string',
            'reverb_app_key' => 'nullable|string',
            'reverb_app_secret' => 'nullable|string',
            'reverb_host' => 'nullable|string',
            'reverb_port' => 'nullable|string',
            'reverb_scheme' => 'nullable|string|in:http,https',
            'reverb_server_port' => 'nullable|string',
        ]);

        try {
            $this->updateEnv([
                'FRONTEND_URL' => $request->frontend_url,
                'GOOGLE_MAPS_API_KEY' => $request->google_maps_api_key,
                'STRIPE_KEY' => $request->stripe_key,
                'STRIPE_SECRET' => $request->stripe_secret,
                'STRIPE_WEBHOOK_SECRET' => $request->stripe_webhook_secret,
                'CASHIER_CURRENCY' => $request->cashier_currency,
                'WEBHOOK_REDIRECT_URL' => $request->webhook_redirect_url,
                'REVERB_APP_ID' => $request->reverb_app_id,
                'REVERB_APP_KEY' => $request->reverb_app_key,
                'REVERB_APP_SECRET' => $request->reverb_app_secret,
                'REVERB_HOST' => $request->reverb_host,
                'REVERB_PORT' => $request->reverb_port,
                'REVERB_SCHEME' => $request->reverb_scheme,
                'REVERB_SERVER_PORT' => $request->reverb_server_port,
            ]);

            return back()->with('success', 'Integration settings updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    protected function updateEnv(array $data)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }
        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $value = $value ?? '';
            // Escape any double quotes in the value
            $escapedValue = str_replace('"', '\"', $value);
            // Wrap in double quotes if there are spaces or special characters
            if (preg_match('/\s/', $escapedValue) || strpbrk($escapedValue, '#=[]{}|&*~?')) {
                $escapedValue = '"' . $escapedValue . '"';
            }

            // Check if key exists (even if commented out, or at the start of line)
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
