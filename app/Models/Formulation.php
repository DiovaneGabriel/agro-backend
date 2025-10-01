<?php
// app/Models/Formulation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formulation extends Model
{
    protected $connection = 'cms';
    protected $fillable = ['description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
