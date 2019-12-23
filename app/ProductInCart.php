<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductInCart extends Model
{
    protected $table = 'product_in_carts';
    protected $fillable = ['product_id', 'variation_id', 'location_id', 'product_quantity', 'uid', 'selling_group_id', 'delivery_uid', 'res_table_id'];
}
