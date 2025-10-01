<?php
// app/Models/PragueCommonName.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PragueCommonName extends Model
{
    protected $connection = 'cms';
    protected $fillable = ['prague_id', 'name'];

    public function prague(): BelongsTo
    {
        return $this->belongsTo(Prague::class);
    }
}
