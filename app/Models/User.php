<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerificationCodeNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'provider',
        'provider_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function verificationCodes()
    {
        return $this->hasMany(VerificationCode::class);
    }

    /**
     * Generate and send a verification code
     */
    public function sendVerificationCode(string $type = 'email_verification'): VerificationCode
    {
        // Invalidate previous unused codes
        $this->verificationCodes()
            ->where('type', $type)
            ->where('used', false)
            ->update(['used' => true]);

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create verification code
        $verificationCode = $this->verificationCodes()->create([
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        // Send notification
        $this->notify(new VerificationCodeNotification($verificationCode));

        return $verificationCode;
    }

    /**
     * Verify a code
     */
    public function verifyCode(string $code, string $type): bool
    {
        $verificationCode = $this->verificationCodes()
            ->where('code', $code)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return false;
        }

        $verificationCode->update(['used' => true]);

        if ($type === 'email_verification') {
            $this->update(['email_verified_at' => now()]);
        }

        return true;
    }

    /**
     * Get user's cart items
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get user's favorites
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get user's locations/addresses
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get user's reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get user's orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get user's points
     */
    public function points()
    {
        return $this->hasMany(Point::class);
    }

    /**
     * Get user's available points
     */
    public function availablePoints()
    {
        return $this->hasMany(Point::class)->available();
    }

    /**
     * Get total available points
     */
    public function getTotalAvailablePointsAttribute(): int
    {
        return Point::getAvailablePoints($this->id);
    }

    /**
     * Get user's product views
     */
    public function productViews()
    {
        return $this->hasMany(ProductView::class);
    }
}
