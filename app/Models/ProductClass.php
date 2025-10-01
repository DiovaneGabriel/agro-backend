<?php
// app/Models/ProductClass.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductClass extends Model
{
    protected $fillable = ['product_id', 'class_id', 'action_mechanism_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function agroClass(): BelongsTo
    {
        return $this->belongsTo(AgroClass::class, 'class_id');
    }

    public function actionMechanism(): BelongsTo
    {
        return $this->belongsTo(ActionMechanism::class);
    }
}
