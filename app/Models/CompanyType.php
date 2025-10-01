<?php
// app/Models/CompanyType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyType extends Model
{
    protected $fillable = ['name'];

    public function productCompanies(): HasMany
    {
        return $this->hasMany(ProductCompany::class);
    }
}
