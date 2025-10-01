<?php
// app/Models/ActionMechanism.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionMechanism extends Model
{
    protected $fillable = ['name'];

    public function products(): BelongsToMany
    {
        // mesmo pivot product_classes, pela coluna action_mechanism_id
        return $this->belongsToMany(Product::class, 'product_classes', 'action_mechanism_id', 'product_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    public function productClasses(): HasMany
    {
        return $this->hasMany(ProductClass::class);
    }
}
