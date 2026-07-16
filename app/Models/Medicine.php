<?php

namespace App\Models;

use App\Models\Category;
use App\Models\MedicineBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Medicine extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'barcode', 'category_id'];
    
    //
    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function batches() {
        return $this->hasMany(MedicineBatch::class);
    }
}
