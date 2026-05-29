<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'receipt_number', 'patient_id', 'cashier_id', 'prescription_id',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'amount_paid', 'change_amount', 'payment_method', 'status', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2', 'change_amount' => 'decimal:2',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function prescription() { return $this->belongsTo(Prescription::class); }
    public function items() { return $this->hasMany(SaleItem::class); }
}
