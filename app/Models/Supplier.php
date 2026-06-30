<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'contact', 'email', 'phone', 'address', 'notes', 'active', 'payment_terms'];

    protected $casts = ['active' => 'boolean'];

    public function paymentTermsDays(): ?int
    {
        return match($this->payment_terms) {
            '30j'  => 30,
            '60j'  => 60,
            '90j'  => 90,
            default => null, // immediate ou custom
        };
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
