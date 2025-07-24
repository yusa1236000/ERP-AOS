<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForQuotation extends Model
{
    use HasFactory;

    protected $table = 'request_for_quotations';
    protected $primaryKey = 'rfq_id';
    protected $fillable = [
        'rfq_number',
        'rfq_date',
        'validity_date',
        'status'
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'validity_date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(RFQLine::class, 'rfq_id');
    }

    public function vendorQuotations()
    {
        return $this->hasMany(VendorQuotation::class, 'rfq_id');
    }

    public function selectedVendors()
    {
        return $this->hasMany(RFQVendor::class, 'rfq_id')->selected();
    }
}
