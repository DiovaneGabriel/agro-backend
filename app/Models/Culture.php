<?php
// app/Models/Culture.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Culture extends Model
{
    protected $fillable = ['name'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_cultures')
            ->withTimestamps();
    }

    public function productCultures(): HasMany
    {
        return $this->hasMany(ProductCulture::class);
    }
}
