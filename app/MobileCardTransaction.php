<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MobileCardTransaction extends Model
{
    protected $table = "mobile_card_transactions";
    protected $fillable = [
    	'business_id',
	    'location_id',
	    'type',
	    'status',
	    'payment_status',
	    'invoice_no',
	    'transaction_date',
	    'tax_amount',
	    'final_total',
	    'created_by'
    ];
}
