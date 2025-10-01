<?php
// app/Models/ActionMode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionMode extends Model
{
    protected $connection = 'cms';
    protected $fillable = ['description'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_action_modes')
            ->withTimestamps();
    }

    public function productActionModes(): HasMany
    {
        return $this->hasMany(ProductActionMode::class);
    }
}
