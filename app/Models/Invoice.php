<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    protected $fillable = [
        'invoice_id',
        'serial_number',
        'customer_name',
        'customer_email',
        'customer_tax_number',
        'customer_location',
        'service',
        'price',
        'discount',
        'invoice_date',
        'is_paid',
    ];
}
