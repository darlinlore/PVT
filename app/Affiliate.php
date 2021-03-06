<?php

namespace App;

use App\Http\Controllers\Api\V1\AffiliateController;
use Carbon;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Support\Facades\Storage;
use Util;

class Affiliate extends Model
{
    use Traits\EloquentGetTableNameTrait;
    use Traits\RelationshipsTrait;

    public $relationships = ['City', 'AffiliateState'];
    // protected $appends = ['picture_saved', 'fingerprint_saved', 'full_name'];
    // protected $hidden = ['pivot'];
    protected $fillable = [
        'affiliate_state_id',
        'city_identity_card_id',
        'city_birth_id',
        'degree_id',
        'unit_id',
        'category_id',
        'pension_entity_id',
        'identity_card',
        'registration',
        'type',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'gender',
        'civil_status',
        'birth_date',
        'date_entry',
        'date_death',
        'reason_death',
        'date_derelict',
        'reason_derelict',
        'change_date',
        'phone_number',
        'cell_phone_number',
        'afp',
        'nua',
        'item',
        'service_years',
        'service_months',
        'death_certificate_number',
        'due_date',
        'is_duedate_undefined',
        'affiliate_registration_number',
        'file_code',
        'account_number',
        'financial_entity_id',
        'sigep_status'
      ];

    public function getTitleAttribute()
    {
      $data = "";
      if ($this->degree) $data = $this->degree->shortened;;
      return $data;
    }

    public function getPictureSavedAttribute()
    {
        try {
            $base_path = 'picture/';
            return Storage::disk('ftp')->exists($base_path . $this->id . '_perfil.jpg');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getIdentityCardExtAttribute()
    {
        $data = $this->identity_card;
        if ($this->city_identity_card) $data .= ' ' . $this->city_identity_card->first_shortened;
        return $data;
    }

    public function getFullUnitAttribute()
    {
        $data = "";
        if ($this->unit) $data .= ' ' . $this->unit->district.' - '.$this->unit->name.' ('.$this->unit->shortened.')';
        return $data;
    }

    public function getCivilStatusGenderAttribute()
    {
        return Util::get_civil_status($this->civil_status, $this->gender);
    }

    public function getFingerprintSavedAttribute()
    {
        try {
            $base_path = 'picture/';
            $fingerprint_pictures = ['_left_four.png', '_right_four.png', '_thumbs.png'];
            $fingerprint_exists = false;
            foreach ($fingerprint_pictures as $picture) {
                $fingerprint_exists |= Storage::disk('ftp')->exists($base_path . $this->id . $picture);
            }
            return boolval($fingerprint_exists);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFullNameAttribute()
    {
      return preg_replace('/[[:blank:]]+/', ' ', join(' ', [$this->first_name, $this->second_name, $this->last_name, $this->mothers_last_name]));
    }

    public function getDeadAttribute()
    {
        return ($this->date_death != null || $this->reason_death != null || $this->death_certificate_number != null);
    }

    public function getDefaultedLenderAttribute()
    {
        $loans = $this->loans()->whereHas('state', function($q) {
            $q->where('name', 'Desembolsado');
        })->get();
        foreach ($loans as $loan) {
            if ($loan->defaulted) return true;
        }
        return false;
    }

    public function getDefaultedGuarantorAttribute()
    {
        $loans = $this->guarantees()->whereHas('state', function($q) {
            $q->where('name', 'Desembolsado');
        })->get();
        foreach ($loans as $loan) {
            if ($loan->defaulted) return true;
        }
        return false;
    }

    public function getDisbursementLoansAttribute()
    {
        $loans = $this->loans()->whereHas('state', function($q) {
            $q->where('name', 'Desembolsado');
        })->get();
        return $loans;
    }

    public function getProcessLoansAttribute()
    {
        $loans = $this->loans()->whereHas('state', function($q) {
            $q->where('name', 'En Proceso');
        })->get();
        return $loans;
    }

    public function getAdministrativeAttribute()
    {
      $data = $this->degree;
      if($this->degree)
        if(strstr($this->degree->name,'administrativo'))
        $data = true;
      return $data;
    }

    public function getCategoryAttribute()
    {
      $category =  Category::whereId($this->category_id)->first();
      if(count($this->contributions)>0) {
        $contribution = $this->contributions->last();
        if($contribution->base_wage>0) {
          $contribution_category = intval($contribution->seniority_bonus*100/$contribution->base_wage);
          $categories = Category::get();
          foreach($categories as $cat){
            if(round($cat->percentage*100) == ($contribution_category))
              $category = $cat;
          }
        }
      }
        unset ($this->contributions);
      return $category;
    }

    public function degree()
    {
      return $this->belongsTo(Degree::class);
    }
    public function unit()
    {
      return $this->belongsTo(Unit::class);
    }
    public function city_identity_card()
    {
      return $this->belongsTo(City::class,'city_identity_card_id', 'id');
    }
    public function affiliate_state()
    {
      return $this->belongsTo(AffiliateState::class);
    }
    public function city_birth()
    {
      return $this->belongsTo(City::class, 'city_birth_id', 'id');
    }
    public function pension_entity()
    {
      return $this->belongsTo(PensionEntity::class);
    }
    public function financial_entity()
    {
      return $this->belongsTo(FinancialEntity::class);
    }
      // add records
    public function records()
    {
      return $this->morphMany(Record::class, 'recordable');
    }
      //address
    public function addresses()
    {
      return $this->morphToMany(Address::class, 'addressable')->withTimestamps()->latest('updated_at');
    }

    public function getAddressAttribute()
    {
        return $this->addresses()->latest()->first();
    }

    //spouses
    public function spouses()
    {
      return $this->hasMany(Spouse::class);
    }
    public function getSpouseAttribute()
    {
      return $this->spouses()->first();
    }
    //contributions
    public function contributions()
    {
      return $this->hasMany(Contribution::class);
    }

    public function observations()
    {
        return $this->morphMany(Observation::class, 'observable')->latest('updated_at');
    }

    public function guarantees()
    {
        return $this->belongsToMany(Loan::class, 'loan_affiliates')->withPivot(['payment_percentage'])->whereGuarantor(true)->orderBy('loans.created_at', 'desc');
    }

    public function loans()
    {
        return $this->belongsToMany(Loan::class, 'loan_affiliates')->withPivot(['payment_percentage'])->whereGuarantor(false)->orderBy('loans.created_at', 'desc');
    }

    public function active_loans()
    {
        return $this->verify_balance($this->loans);
    }
    public function active_guarantees()
    {
        return $this->verify_balance($this->guarantees);
    }

    private function verify_balance($loans)
    {
        $active_loans = [];
        foreach ($loans as $loan) {
            $loan->balance = $loan->balance;
            if ($loan->balance > 0) {
                $loan->estimated_quota = $loan->estimated_quota;
                array_push($active_loans, $loan);
            }
        }
        return $active_loans;
    }
    public function inactive_loans()
    {
        return $this->verify_balance_liquidated($this->loans);
    }

    private function verify_balance_liquidated($loans)
    {
        $inactive_loans = [];
        foreach ($loans as $loan) {
            $loan->balance = $loan->balance;
            if ($loan->balance == 0) {
                array_push($inactive_loans, $loan);
            }
        }
        return $inactive_loans;
    }

    //document
    public function submitted_documents()
    {
        return $this->hasMany(AffiliateSubmittedDocument::class);
    }

    public function disbursements()
    {
        return $this->morphMany(Loan::class, 'disbursable');
    }

    public function getCpopAttribute(){
      $cpop = false;
      if($this->defaulted_lender) return $cpop ;
      $date_now = Carbon::now()->format('Y-m-d');
      $date_year_ago = Carbon::parse($date_now)->addYear(-1)->toDateString(); // para verificar registro de pagos un hasta 1 año a tras
      $active_loans = $this->active_loans();
      $inactive_loans = $this->inactive_loans();
      $loans = array_merge($active_loans, $inactive_loans ); // se debe verificar en pagados y en vigencia
      if(count($loans)>=1){
        foreach($loans as $loan){
          $c = 0;
          if(count($loan->payments)>=12){
            foreach ($loan->payments as $payment) {
              if($c < 12){
                if(($payment->estimated_date >= $date_year_ago) && ($payment->estimated_date <= $date_now)) $c++; $cpop = true;
                if(!$payment->penal_payment == 0 ) { // verificar que en ese periodo no haya tenido penalidad
                  $cpop = false;
                  return $cpop;
                }
              }
            }
          }
        }
      }
      return $cpop;
    }

    public function test_guarantor($modality){
      $guarantor = false;
      if($modality){
          $modality = ProcedureModality::findOrFail($modality); //evaluando categoria acorde a la modalidad
          if($modality->loan_modality_parameter->min_guarantor_category <= $this->category->percentage && $this->category->percentage <= $modality->loan_modality_parameter->max_guarantor_category) $guarantor = true;
      }else{
          $loan_modality_parameter = LoanModalityParameter::get();
          if( $loan_modality_parameter->min('min_guarantor_category')<= $this->category->percentage && $this->category->percentage <= $loan_modality_parameter->max('max_guarantor_category')) $guarantor = true; //evaluando categoria sin tomar en cuenta la modalidad
      }
      if($guarantor){
          $loan_global_parameter = LoanGlobalParameter::latest()->first();
          if($this->affiliate_state->affiliate_state_type->name == 'Activo'){
              if($loan_global_parameter->max_guarantor_active <= count($this->active_guarantees())) $guarantor = false;
          }
          if($this->affiliate_state->affiliate_state_type->name == 'Pasivo'){
              if($loan_global_parameter->max_guarantor_passive <= count($this->active_guarantees())) $guarantor = false;
          }
          if($this->affiliate_state->affiliate_state_type->name != 'Activo' && $this->affiliate_state->affiliate_state_type->name != 'Pasivo') $guarantor = false; // en otro caso no corresponde ya que seria Disponibilidad A o C
          if($this->defaulted_lender || $this->defaulted_guarantor) $guarantor = false;
      }
      return response()->json([
          'affiliate' => AffiliateController::append_data($this, true),
          'guarantor' => $guarantor,
          'active_guarantees_quantity' => count($this->active_guarantees()),
          'guarantor_information' => self::verify_information($this)

      ]);
  }

  public static function verify_information(Affiliate $affiliate)
  {
      $needed_keys = ['city_identity_card', 'affiliate_state', 'city_birth', 'category', 'address'];
      $information = true;
      foreach ($needed_keys as $key) {
          if (!$affiliate[$key]) $information = false;//abort(409, 'Debe actualizar los datos personales de los garantes');
      }
      return $information;
  }
}
