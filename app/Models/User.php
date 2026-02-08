<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the face profiles for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<FaceProfile, $this>
     */
    public function faceProfiles()
    {
        return $this->hasMany(FaceProfile::class);
    }

    /**
     * Check if user has any registered faces
     */
    public function hasFaceRegistered(): bool
    {
        return $this->faceProfiles()->exists();
    }

    /**
     * Get count of registered faces
     */
    public function faceCount(): int
    {
        return $this->faceProfiles()->count();
    }

    /**
     * Get attendance records for the user
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get offsite attendance entries created by this admin
     * @return HasMany<Attendance, $this>
     */
    public function offsiteAttendancesCreated(): HasMany
    {
        return $this->hasMany(Attendance::class, 'created_by_admin_id');
    }

    /**
     * Get leaves for the user
     * @return HasMany<Leave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
