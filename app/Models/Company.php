<?php
// app/Models/Company.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $connection = 'cms';
    protected $fillable = ['name', 'country_id'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function products(): BelongsToMany
    {
        // via product_companies
        return $this->belongsToMany(Product::class, 'product_companies')
            ->withPivot('company_type_id')
            ->withTimestamps();
    }

    public function productCompanies(): HasMany
    {
        return $this->hasMany(ProductCompany::class);
    }
}
