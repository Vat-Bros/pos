<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price'
    ];

    protected static function booted()
    {
        static::updating(function ($item) {

            if ($item->order->status !== 'pending') {
                abort(403, 'Cannot update item, order locked');
            }

        });

        static::deleting(function ($item) {

            if ($item->order->status !== 'pending') {
                abort(403, 'Cannot delete item, order locked');
            }

        });
    }

}
 