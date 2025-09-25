<?php
// app/Models/ProductCulture.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCulture extends Model
{
    protected $fillable = ['product_id', 'culture_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function culture(): BelongsTo
    {
        return $this->belongsTo(Culture::class);
    }
}
