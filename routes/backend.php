<?php

use App\Http\Controllers\Web\backend\admin\CampaignController;
use App\Http\Controllers\Web\backend\admin\CustomerController;
use App\Http\Controllers\Web\backend\admin\DeliveryManController;
use App\Http\Controllers\Web\backend\admin\DeliveryOptionController;
use App\Http\Controllers\Web\backend\admin\FAQController;
use App\Http\Controllers\Web\backend\admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\backend\admin\OrderSettingController;
use App\Http\Controllers\Web\backend\admin\RideController as AdminRideController;
use App\Http\Controllers\Web\backend\admin\RiderController;
use App\Http\Controllers\Web\backend\admin\SettlementController;
use App\Http\Controllers\Web\backend\admin\SupportChatController;
use App\Http\Controllers\Web\backend\admin\TransportTypeCategoryController;
use App\Http\Controllers\Web\backend\admin\TransportTypeController;
use App\Http\Controllers\Web\backend\admin\VendorController;
use App\Http\Controllers\Web\backend\admin\VoucherController;
use App\Http\Controllers\Web\backend\DashboardController;
use App\Http\Controllers\Web\backend\InterestCategoryController;
use App\Http\Controllers\Web\backend\InterestController;
use App\Http\Controllers\Web\backend\PremissionController;
use App\Http\Controllers\Web\backend\ProductCategoryController;
use App\Http\Controllers\Web\backend\ProductController;
use App\Http\Controllers\Web\backend\RelationCategoryController;
use App\Http\Controllers\Web\backend\RelationController;
use App\Http\Controllers\Web\backend\RoleController;
use App\Http\Controllers\Web\backend\SettingController;
use App\Http\Controllers\Web\backend\settings\DynamicPagesController;
use App\Http\Controllers\Web\backend\settings\ProfileSettingController;
use App\Http\Controllers\Web\backend\UserController;
use Illuminate\Support\Facades\Route;



//Dashboard Routes
Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard', 'dashboard')->name('admin.dashboard');
});

// Settings Route
Route::controller(SettingController::class)->group(function () {
    Route::get('/general/setting', 'create')->name('general.setting');
    Route::post('/system/update', 'update')->name('system.update');
    Route::get('/system/setting', 'systemSetting')->name('system.setting');
    Route::post('/system/setting/update', 'systemSettingUpdate')->name('system.settingupdate');
    Route::get('/setting', 'adminSetting')->name('admin.setting');
    Route::get('/stripe', 'stripe')->name('admin.setting.stripe');
    Route::post('/stripe', 'stripestore')->name('admin.setting.stripestore');
    Route::get('/paypal', 'paypal')->name('admin.setting.paypal');
    Route::post('/paypal', 'paypalstore')->name('admin.setting.paypalstore');
    Route::get('/mail', 'mail')->name('admin.setting.mail');
    Route::post('/mail', 'mailstore')->name('admin.setting.mailstore');
    Route::get('/integration/setting', 'integrationSetting')->name('admin.setting.integration');
    Route::post('/integration/setting', 'integrationSettingStore')->name('admin.setting.integration.store');
    Route::post('/setting/update', 'adminSettingUpdate')->name('admin.settingupdate');
});

// Profile Settings Controller
Route::controller(ProfileSettingController::class)->group(function () {
    Route::get('/profile', 'index')->name('profile');
    Route::post('/profile/update', 'updateProfile')->name('profile.update');
    Route::post('/profile/update/password', 'updatePassword')->name('profile.update.password');
    Route::post('/profile/update/profile-picture', 'updateProfilePicture')->name('profile.update.profile.picture');
    Route::get('/checkusername', 'checkusername')->name('checkusername');
});

// FAQ Route
Route::controller(FAQController::class)->group(function () {
    Route::get('/faq', 'index')->name('faq.index');
    Route::get('/faq/get', 'get')->name('faq.get');
    Route::post('/faq/priorities', 'priority')->name('faq.priority');
    Route::get('/faq/status', 'status')->name('faq.status');
    Route::post('/faq/store', 'store')->name('faq.store');
    Route::post('/faq/update', 'update')->name('faq.update');
    Route::get('/faq/destroy/{id}', 'destroy')->name('faq.destroy');
});

// Customer Route
Route::group(['prefix' => 'customers', 'as' => 'admin.customer.'], function () {
    Route::controller(CustomerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::get('/status/{id}', 'status')->name('status');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
    });
});

// Vendor Route
Route::group(['prefix' => 'vendors', 'as' => 'admin.vendor.'], function () {
    Route::controller(VendorController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::get('/status/{id}', 'status')->name('status');
        Route::get('/activate/{id}', 'activate')->name('activate');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
    });
});

// Rider Route
Route::group(['prefix' => 'riders', 'as' => 'admin.rider.'], function () {
    Route::controller(RiderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::get('/status/{id}', 'status')->name('status');
        Route::get('/activate/{id}', 'activate')->name('activate');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
    });
});

// Delivery Man Route
Route::group(['prefix' => 'delivery-men', 'as' => 'admin.delivery_man.'], function () {
    Route::controller(DeliveryManController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::get('/status/{id}', 'status')->name('status');
        Route::get('/activate/{id}', 'activate')->name('activate');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
    });
});


//Dynamic Pages Route
Route::controller(DynamicPagesController::class)->group(function () {
    Route::get('/dynamicpages', 'index')->name('dynamicpages.index');
    Route::get('/dynamicpages/create', 'create')->name('dynamicpages.create');
    Route::get('/dynamicpages/edit/{id}', 'edit')->name('dynamicpages.edit');
    Route::post('/dynamicpages/store', 'store')->name('dynamicpages.store');
    Route::post('/dynamicpages/update/{id}', 'update')->name('dynamicpages.update');
    Route::delete('/dynamicpages/destroy/{id}', 'destroy')->name('dynamicpages.destroy');
    Route::post('/dynamicpages/status/{id}', 'changeStatus')->name('dynamicpages.status');
    Route::post('/dynamicpages/bulk-delete', 'bulkDelete')->name('dynamicpages.bulk-delete');
});

Route::prefix('permissions')->controller(PremissionController::class)->group(function () {
    Route::get('/list', 'index')->name('admin.permissions.list');
    Route::get('/create', 'create')->name('admin.permissions.create');
});

Route::prefix('role')->controller(RoleController::class)->group(function () {
    Route::get('/list', 'index')->name('admin.role.list');
    Route::get('/create', 'create')->name('admin.role.create');
    Route::post('/store', 'store')->name('admin.role.store');
    Route::get('/edit/{id}', 'edit')->name('admin.role.edit');
    Route::post('/update/{id}', 'update')->name('admin.role.update');
    Route::delete('/destroy/{id}', 'destroy')->name('admin.role.destroy');
});

// Delivery Options Route
Route::group(['prefix' => 'delivery-options', 'as' => 'admin.delivery_options.'], function () {
    Route::controller(DeliveryOptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::get('/status/{id}', 'status')->name('status');
        Route::get('/set-default/{id}', 'setDefault')->name('set_default');
    });
});

// Vouchers Route
Route::group(['prefix' => 'vouchers', 'as' => 'admin.vouchers.'], function () {
    Route::controller(VoucherController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::get('/status/{id}', 'status')->name('status');
    });
});

// Categories Route
Route::group(['prefix' => 'categories', 'as' => 'admin.categories.'], function () {
    Route::controller(App\Http\Controllers\Web\backend\admin\CategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::get('/status/{id}', 'status')->name('status');
    });
});

// Order Settings Route
Route::controller(OrderSettingController::class)->group(function () {
    Route::get('/order-settings', 'index')->name('admin.order_settings.index');
    Route::post('/order-settings/update', 'update')->name('admin.order_settings.update');
});

// Support Chat Route
Route::group(['prefix' => 'support-chat', 'as' => 'admin.support_chat.'], function () {
    Route::controller(SupportChatController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/send/{id}', 'sendMessage')->name('send');
        Route::post('/message/{id}/update', 'updateMessage')->name('update_message');
        Route::post('/message/{id}/delete', 'deleteMessage')->name('delete_message');
    });
});

// Settlements Route
Route::group(['prefix' => 'settlements', 'as' => 'admin.settlements.'], function () {
    Route::controller(SettlementController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/pending-users', 'pendingUsers')->name('pending_users');
        Route::post('/create', 'create')->name('create');
        Route::post('/process/{id}', 'process')->name('process');
    });
});

// Rides Route
Route::group(['prefix' => 'rides', 'as' => 'admin.rides.'], function () {
    Route::controller(AdminRideController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/show/{id}', 'show')->name('show');
    });
});

// Orders Route
Route::group(['prefix' => 'orders', 'as' => 'admin.orders.'], function () {
    Route::controller(AdminOrderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/show/{id}', 'show')->name('show');
        Route::patch('/status/{id}', 'updateStatus')->name('status');
    });
});

// Transport Type Categories Route
Route::group(['prefix' => 'transport-type-categories', 'as' => 'admin.transport_type_categories.'], function () {
    Route::controller(TransportTypeCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::get('/status/{id}', 'status')->name('status');
    });
});

// Transport Types Route
Route::group(['prefix' => 'transport-types', 'as' => 'admin.transport_types.'], function () {
    Route::controller(TransportTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/delete', 'destroy')->name('destroy');
        Route::get('/status/{id}', 'status')->name('status');
    });
});

// Ads Route
Route::group(['prefix' => 'ads', 'as' => 'admin.ads.'], function () {
    Route::controller(App\Http\Controllers\Web\backend\admin\AdController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('/status/{id}', 'status')->name('status');
        Route::post('/delete/{id}', 'destroy')->name('delete');
        Route::post('/send-mail/{id}', 'sendMail')->name('sendMail');
    });
});

// Campaign Route
Route::group(['prefix' => 'campaigns', 'as' => 'admin.campaigns.'], function () {
    Route::controller(CampaignController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/toggle-status/{id}', 'toggleStatus')->name('status');
    });
});


