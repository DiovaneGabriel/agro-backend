<?php
// app/Models/Prague.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Prague extends Model
{
    protected $connection = 'cms';
    protected $fillable = ['scientific_name'];

    public function commonNames(): HasMany
    {
        return $this->hasMany(PragueCommonName::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_pragues')
            ->withTimestamps();
    }

    public function productPragues(): HasMany
    {
        return $this->hasMany(ProductPrague::class);
    }
}
