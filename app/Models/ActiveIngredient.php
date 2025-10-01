<?php
// app/Models/ActiveIngredient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActiveIngredient extends Model
{
    protected $fillable = ['name', 'chemical_group_id'];

    public function chemicalGroup(): BelongsTo
    {
        return $this->belongsTo(ChemicalGroup::class);
    }

    // Many-to-many via product_active_ingredients
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_active_ingredients')
            ->withTimestamps();
    }

    // Acesso direto ao pivot (opcional)
    public function productActiveIngredients(): HasMany
    {
        return $this->hasMany(ProductActiveIngredient::class);
    }
}
