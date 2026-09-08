<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const DOCUMENT_FIELDS = [
        'driving_license_front' => 'driving_license_front_path',
        'driving_license_back' => 'driving_license_back_path',
        'identity_front' => 'identity_front_path',
        'identity_back' => 'identity_back_path',
    ];

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'driving_license_number',
        'driving_license_country',
        'driving_license_issue_date',
        'driving_license_expiry_date',
        'driving_license_front_path',
        'driving_license_back_path',
        'identity_front_path',
        'identity_back_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'driving_license_issue_date' => 'date',
        'driving_license_expiry_date' => 'date',
        'driver_verification_submitted_at' => 'datetime',
        'driver_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'driving_license_front_path',
        'driving_license_back_path',
        'identity_front_path',
        'identity_back_path',
    ];

    protected $appends = [
        'age',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_verified_by');
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function hasDocument(string $field): bool
    {
        return filled($this->{$field});
    }

    public function hasCompleteDriverProfile(): bool
    {
        return filled($this->date_of_birth)
            && filled($this->driving_license_number)
            && filled($this->driving_license_front_path)
            && filled($this->driving_license_back_path)
            && filled($this->identity_front_path)
            && filled($this->identity_back_path);
    }

    public function isDriverVerified(): bool
    {
        return $this->driver_verification_status === self::STATUS_VERIFIED
            && $this->hasCompleteDriverProfile();
    }

    public function isPendingVerification(): bool
    {
        return $this->driver_verification_status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->driver_verification_status === self::STATUS_REJECTED;
    }
}
