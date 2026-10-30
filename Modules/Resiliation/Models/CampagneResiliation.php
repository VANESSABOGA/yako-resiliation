<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CampagneResiliation extends Model
{
    use SoftDeletes;

    protected $table = 'resiliation_campagnes';

    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function contrats(): HasMany
    {
        return $this->hasMany(ContratCampagne::class, 'campagne_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActives($query)
    {
        return $query->where('statut', 'ACTIVE');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    public function estActive(): bool
    {
        return $this->statut === 'ACTIVE';
    }
}
