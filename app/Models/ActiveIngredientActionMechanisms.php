<?php
// app/Models/ToxicologicalClass.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveIngredientActionMechanisms extends Model
{
    protected $fillable = ['active_ingredient_id', 'class_id', 'action_mechanism_id'];
}
