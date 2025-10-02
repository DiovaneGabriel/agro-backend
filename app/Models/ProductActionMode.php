<?php
// app/Models/ProductActionMode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductActionMode extends Model
{
    // protected $connection = 'cms';
    protected $fillable = ['product_id', 'action_mode_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actionMode(): BelongsTo
    {
        return $this->belongsTo(ActionMode::class);
    }
}
