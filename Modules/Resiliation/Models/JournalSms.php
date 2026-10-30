<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class JournalSms extends Model
{
    protected $table = 'resiliation_journal_sms';

    protected $fillable = [
        'contrat_id', 'type_sms', 'message_id_fournisseur',
        'numero_destinataire', 'contenu_integral',
        'date_envoi', 'date_reception', 'statut_accuse', 'motif_echec',
        'cout', 'agent_id',
    ];

    protected $casts = [
        'date_envoi'    => 'datetime',
        'date_reception'=> 'datetime',
        'cout'          => 'decimal:2',
    ];

    // ⚠️ Preuve opposable : pas de mise à jour ni suppression exposée
    // On utilise uniquement create() et les callbacks de mise à jour du statut d'accusé.

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratCampagne::class, 'contrat_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
