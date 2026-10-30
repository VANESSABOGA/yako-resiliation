<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureImpayee extends Model
{
    protected $table = 'resiliation_factures_impayees';

    protected $fillable = [
        'contrat_id', 'numero_facture', 'date_echeance',
        'montant', 'montant_paye', 'statut',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'montant'       => 'decimal:2',
        'montant_paye'  => 'decimal:2',
    ];

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratCampagne::class, 'contrat_id');
    }

    public function getResteAPayerAttribute(): float
    {
        return (float) $this->montant - (float) $this->montant_paye;
    }
}
