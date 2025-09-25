<?php
// app/Models/AgroClass.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgroClass extends Model
{
    // A tabela se chama "classes", então definimos explicitamente:
    protected $table = 'classes';
    protected $fillable = ['name'];

    public function products(): BelongsToMany
    {
        // Tabela product_classes com campo extra action_mechanism_id
        return $this->belongsToMany(Product::class, 'product_classes', 'class_id', 'product_id')
            ->withPivot('action_mechanism_id')
            ->withTimestamps();
    }

    public function productClasses(): HasMany
    {
        return $this->hasMany(ProductClass::class, 'class_id');
    }
}
