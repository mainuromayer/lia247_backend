<?php

namespace App\Models;

use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use App\Traits\HasStripeCustomer;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasStripeCustomer;

    protected $guard_name = 'web';

    protected $fillable = [
        'user_type',
        'avatar',
        'cover_photo',
        'name',
        'email',
        'original_email',
        'email_verified_at',
        'phone',
        'password',
        'description',
        'address',
        'country',
        'city',
        'zip_code',
        'otp',
        'otp_created_at',
        'otp_expires_at',
        'status',
        'is_admin',
        'nid',
        'gender',
        'date_of_birth',
        'latitude',
        'longitude',
        'terms_and_conditions',
        'push_notification',
        'order_request_notification',
        'email_notification',
        'is_online'
    ];

    protected $casts = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terms_and_conditions' => 'boolean',
            'push_notification' => 'boolean',
            'order_request_notification' => 'boolean',
            'email_notification' => 'boolean',
            'is_online' => 'boolean',
            'user_type' => 'string',
            'otp_created_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'is_admin' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function firebaseTokens()
    {
        return $this->hasMany(FirebaseToken::class);
    }


    public function socialMedia()
    {
        return $this->hasMany(SocialMedia::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAvatarAttribute($value): string|null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && !empty($value)) {
            return url($value);
        }
        return $value;
    }

    public function getCoverPhotoAttribute($value): string|null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && !empty($value)) {
            return url($value);
        }
        return $value;
    }

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function vehicleInformation(): HasOne
    {
        return $this->hasOne(VehicleInformation::class);
    }

    public function drivingInformation(): HasOne
    {
        return $this->hasOne(DrivingInformation::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }


    public function supportChats()
    {
        return $this->hasMany(SupportChat::class, 'user_id');
    }

    public function createPaymentIntent($amount, array $options = [])
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return PaymentIntent::create(array_merge([
            'amount' => $amount,
            'currency' => env('STRIPE_CURRENCY', 'usd'),
        ], $options));
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'target_id');
    }

    public function deliveryManOrders()
    {
        return $this->hasMany(Order::class, 'delivery_man_id');
    }

    public function getCompletedDeliveriesCountAttribute()
    {
        return $this->deliveryManOrders()->whereIn('status', ['completed', 'delivered'])->count();
    }

    public function getAverageRatingAttribute()
    {
        $role = 'vendor';
        if ($this->user_type === 'rider') $role = 'rider';
        if ($this->user_type === 'delivery_man') $role = 'delivery_man';

        return round($this->reviews()->where('role', $role)->avg('rating') ?? 0, 1);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function riderRideCancellations()
    {
        return $this->hasMany(RiderRideCancellation::class, 'rider_id');
    }

    public function deliveryManOrderCancellations()
    {
        return $this->hasMany(DeliveryManOrderCancellation::class, 'delivery_man_id');
    }
}