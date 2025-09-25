<?php
// app/Models/ProductCompany.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompany extends Model
{
    protected $fillable = ['product_id', 'company_id', 'company_type_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyType(): BelongsTo
    {
        return $this->belongsTo(CompanyType::class);
    }
}
