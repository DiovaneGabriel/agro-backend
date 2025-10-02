<?php
// app/Models/ProductActiveIngredient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductActiveIngredient extends Model
{
    // protected $connection = 'cms';
    protected $fillable = ['product_id', 'active_ingredient_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function activeIngredient(): BelongsTo
    {
        return $this->belongsTo(ActiveIngredient::class);
    }
}
