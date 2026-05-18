<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AltOrder extends Model
{
    protected $table = 'alt_orders';

    protected $fillable = [
        'alt_user_id',
        'total_price',
        'sending_method',
        'transport_detail',
        'payment_method',
        'payment_detail',
        'information',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(AltUser::class, 'alt_user_id');
    }

    public function items()
    {
        return $this->hasMany(AltOrderItem::class, 'alt_order_id');
    }

    public function shipping()
    {
        return $this->hasOne(ShippingDetail::class, 'alt_order_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    public static function orderStates($state = null)
    {
        if ($state === null) {
            $statuses = [];
            foreach (OrderStatus::cases() as $status) {
                $statuses[$status->value] = $status->label();
            }

            return $statuses;
        }

        if ($state instanceof OrderStatus) {
            return $state->label();
        }

        return OrderStatus::from($state)->label();
    }

    public static function placeOrder(array $shipping = [])
    {
        return app(\App\Services\OrderService::class)->placeOrder($shipping);
    }
}
