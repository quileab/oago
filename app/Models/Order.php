<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $user_id
 * @property float $total_price
 * @property string|null $sending_method
 * @property OrderStatus $status
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $items
 */
class Order extends Model
{
    // protected $fillable = ['status'];
    protected $fillable = [
        'user_id',
        'total_price',
        'sending_method',
        'sending_address',
        'sending_city',
        'contact_name',
        'contact_number',
        'transport_detail',
        'payment_method',
        'payment_detail',
        'information',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(ShippingDetail::class);
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

    public static function placeOrder(array $shipping = [])
    {
        return app(\App\Services\OrderService::class)->placeOrder($shipping);
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
}
