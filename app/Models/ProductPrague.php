<?php
// app/Models/ProductPrague.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrague extends Model
{
    protected $fillable = ['product_id', 'prague_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prague(): BelongsTo
    {
        return $this->belongsTo(Prague::class);
    }
}
