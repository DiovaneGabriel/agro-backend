<?php
// app/Models/Country.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name'];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
