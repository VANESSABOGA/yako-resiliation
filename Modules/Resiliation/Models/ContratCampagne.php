<?php

namespace Modules\Resiliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class ContratCampagne extends Model
{
    use SoftDeletes;

    protected $table = 'resiliation_contrats_campagne';

    protected $fillable = [
        'campagne_id', 'nsil_contrat_id', 'code_produit', 'code_formule', 'numero_police',
        'souscripteur_nom', 'assure_nom', 'joignable', 'moyen_contact',
        'email_1', 'email_2', 'telephone_oneci', 'client_telephone',
        'telephone_2', 'telephone_3', 'telephone_4', 'telephone_5',
        'code_conseiller', 'nom_conseiller', 'inspection', 'canal_distribution', 'conseiller_manquant',
        'date_naissance_assure', 'date_effet_reel', 'date_echeance',
        'duree_cotisation_ans', 'code_periodicite', 'periodicite', 'total_prime',
        'statut_nsil', 'etat_contrat_nsil',
        'nb_encaissements', 'date_premier_encaissement', 'date_dernier_encaissement',
        'anciennete', 'nb_enc_theorique', 'cumul_encaissement', 'duree_courue',
        'code_mode_paiement', 'cotisation_annuelle', 'cumul_cotisation_theorique',
        'cumul_cotisation_requis',
        'nb_impayes', 'montant_impaye_total', 'flag_impaye_sup_1', 'pm_contrat',
        'genre',
        'statut', 'date_integration', 'date_envoi_sms', 'date_reception_sms', 'date_limite',
        'agent_id',
    ];

    protected $casts = [
        'joignable'                => 'boolean',
        'conseiller_manquant'      => 'boolean',
        'flag_impaye_sup_1'        => 'boolean',
        'date_naissance_assure'    => 'date',
        'date_effet_reel'          => 'date',
        'date_echeance'            => 'date',
        'date_premier_encaissement'=> 'date',
        'date_dernier_encaissement'=> 'date',
        'date_integration'         => 'datetime',
        'date_envoi_sms'           => 'datetime',
        'date_reception_sms'       => 'datetime',
        'date_limite'              => 'date',
        'total_prime'              => 'decimal:2',
        'cumul_encaissement'       => 'decimal:2',
        'cotisation_annuelle'      => 'decimal:2',
        'cumul_cotisation_theorique'=> 'decimal:2',
        'cumul_cotisation_requis'  => 'decimal:2',
        'montant_impaye_total'     => 'decimal:2',
        'pm_contrat'               => 'decimal:2',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneResiliation::class, 'campagne_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(FactureImpayee::class, 'contrat_id');
    }

    public function journalSms(): HasMany
    {
        return $this->hasMany(JournalSms::class, 'contrat_id');
    }

    public function appels(): HasMany
    {
        return $this->hasMany(AppelAgent::class, 'contrat_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'contrat_id');
    }

    // -----------------------------------------------------------------
    // Accessors / Helpers
    // -----------------------------------------------------------------

    /**
     * Nombre de jours restants avant la date limite (RG-01).
     * Retourne null si pas encore de date limite.
     */
    public function getJoursRestantsAttribute(): ?int
    {
        if (!$this->date_limite) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->date_limite, false);
    }

    /**
     * Téléphone masqué pour affichage (section 9).
     */
    public function getTelephoneMasqueAttribute(): string
    {
        if (!$this->client_telephone) {
            return '—';
        }
        $tel = $this->client_telephone;
        return substr($tel, 0, 2) . str_repeat('*', max(0, strlen($tel) - 4)) . substr($tel, -2);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeEnDelai($query)
    {
        return $query->where('statut', 'EN_DELAI');
    }

    public function scopeARelancer($query)
    {
        return $query->where('statut', 'EN_DELAI')
                     ->whereDate('date_limite', '<=', now()->addDays(7));
    }
}
