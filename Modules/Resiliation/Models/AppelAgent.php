<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AppelAgent extends Model
{
    protected $table = 'resiliation_appels_agents';

    protected $fillable = [
        'contrat_id', 'agent_id',
        'date_heure_appel', 'motif', 'souhaite_payer', 'commentaire',
    ];

    protected $casts = [
        'date_heure_appel' => 'datetime',
        'souhaite_payer'   => 'boolean',
    ];

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratCampagne::class, 'contrat_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
