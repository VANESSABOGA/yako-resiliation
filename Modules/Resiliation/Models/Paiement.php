<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Paiement extends Model
{
    protected $table = 'resiliation_paiements';

    protected $fillable = [
        'contrat_id', 'reference_transaction', 'montant', 'canal',
        'date_paiement', 'statut', 'rapproche_par', 'factures_ids',
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
        'montant'       => 'decimal:2',
        'factures_ids'  => 'array',
    ];

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratCampagne::class, 'contrat_id');
    }

    public function rapprochePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rapproche_par');
    }
}
