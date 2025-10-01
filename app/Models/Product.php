<?php
// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $connection = 'cms';
    protected $fillable = [
        'is_active',
        'is_organic',
        'register_number',
        'formulation_id',
        'concentration',
        'registration_holder_id',
        'toxicological_class_id',
        'environmental_class_id',
        'hrac',
        'wssa',
    ];

    protected $casts = [
        'is_active'  => 'bool',
        'is_organic' => 'bool',
    ];

    // --- FKs principais ---
    public function formulation(): BelongsTo
    {
        return $this->belongsTo(Formulation::class);
    }

    public function registrationHolder(): BelongsTo
    {
        return $this->belongsTo(RegistrationHolder::class);
    }

    public function toxicologicalClass(): BelongsTo
    {
        return $this->belongsTo(ToxicologicalClass::class);
    }

    public function environmentalClass(): BelongsTo
    {
        return $this->belongsTo(EnvironmentalClass::class);
    }

    // --- One-to-many auxiliares ---
    public function brands(): HasMany
    {
        return $this->hasMany(ProductBrand::class);
    }

    public function productActiveIngredients(): HasMany
    {
        return $this->hasMany(ProductActiveIngredient::class);
    }

    public function productClasses(): HasMany
    {
        return $this->hasMany(ProductClass::class);
    }

    public function productActionModes(): HasMany
    {
        return $this->hasMany(ProductActionMode::class);
    }

    public function productCultures(): HasMany
    {
        return $this->hasMany(ProductCulture::class);
    }

    public function productPragues(): HasMany
    {
        return $this->hasMany(ProductPrague::class);
    }

    public function productCompanies(): HasMany
    {
        return $this->hasMany(ProductCompany::class);
    }

    // --- Many-to-many principais ---
    public function activeIngredients(): BelongsToMany
    {
        return $this->belongsToMany(ActiveIngredient::class, 'product_active_ingredients')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(AgroClass::class, 'product_classes', 'product_id', 'class_id')
            ->withPivot('action_mechanism_id')
            ->withTimestamps();
    }

    public function actionMechanisms(): BelongsToMany
    {
        return $this->belongsToMany(ActionMechanism::class, 'product_classes', 'product_id', 'action_mechanism_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    public function actionModes(): BelongsToMany
    {
        return $this->belongsToMany(ActionMode::class, 'product_action_modes')
            ->withTimestamps();
    }

    public function cultures(): BelongsToMany
    {
        return $this->belongsToMany(Culture::class, 'product_cultures')
            ->withTimestamps();
    }

    public function pragues(): BelongsToMany
    {
        return $this->belongsToMany(Prague::class, 'product_pragues')
            ->withTimestamps();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'product_companies')
            ->withPivot('company_type_id')
            ->withTimestamps();
    }

    public function companyTypes(): BelongsToMany
    {
        // acesso indireto aos tipos via pivot
        return $this->belongsToMany(CompanyType::class, 'product_companies', 'product_id', 'company_type_id')
            ->withPivot('company_id')
            ->withTimestamps();
    }
}
