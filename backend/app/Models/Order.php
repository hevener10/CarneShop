<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $appends = [
        'can_be_canceled',
        'can_change_status',
    ];

    protected $fillable = [
        'store_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'delivery_number',
        'delivery_complement',
        'delivery_neighborhood',
        'delivery_city',
        'delivery_state',
        'delivery_reference',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'payment_method',
        'change_for',
        'payment_status',
        'status',
        'admin_notes',
        'cancellation_reason',
        'whatsapp_message',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'change_for' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }

    // Constantes de status.
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Gera um identificador legivel para novos pedidos.
     */
    public function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "PED-{$date}-{$random}";
    }

    /**
     * Restringe a consulta aos pedidos pendentes.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Restringe a consulta aos pedidos ainda ativos.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELED]);
    }

    /**
     * Formata o total do pedido para exibicao monetaria.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Formata a taxa de entrega ou informa entrega gratis.
     */
    public function getFormattedDeliveryFeeAttribute(): string
    {
        return $this->delivery_fee > 0
            ? 'R$ ' . number_format($this->delivery_fee, 2, ',', '.')
            : 'Grátis';
    }

    /**
     * Traduz o status interno para um rotulo amigavel.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_PREPARING => 'Preparando',
            self::STATUS_READY => 'Pronto',
            self::STATUS_DISPATCHED => 'Enviado/Entregue',
            self::STATUS_CANCELED => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Concatena os campos de endereco disponiveis do pedido.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->delivery_address,
            $this->delivery_number,
            $this->delivery_complement,
            $this->delivery_neighborhood,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Expõe ao frontend se o pedido ainda pode ser cancelado.
     */
    public function getCanBeCanceledAttribute(): bool
    {
        return $this->canBeCanceled();
    }

    /**
     * Expõe ao frontend se o status do pedido ainda pode ser alterado.
     */
    public function getCanChangeStatusAttribute(): bool
    {
        return $this->canChangeStatus();
    }

    /**
     * Retorna a loja dona do pedido.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Retorna os itens associados ao pedido.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Recalcula o total com base em subtotal, entrega e desconto.
     */
    public function calculateTotal(): float
    {
        return $this->subtotal + $this->delivery_fee - $this->discount;
    }

    /**
     * Monta a mensagem de WhatsApp usada para encaminhar o pedido.
     */
    public function generateWhatsappMessage(): string
    {
        $message = "*Novo Pedido - {$this->order_number}*\n\n";
        $message .= "*Cliente:* {$this->customer_name}\n";
        $message .= "*Telefone:* {$this->customer_phone}\n\n";

        if ($this->delivery_address) {
            $message .= "*Endereço:* {$this->full_address}\n";
            if ($this->delivery_reference) {
                $message .= "*Referência:* {$this->delivery_reference}\n";
            }
            $message .= "\n";
        }

        $message .= "*Itens do pedido:*\n";

        foreach ($this->items as $item) {
            $message .= "\n{$item->quantity}x {$item->product_name}";

            if ($item->variation_name) {
                $message .= " ({$item->variation_name})";
            }

            if ($item->gramage) {
                $message .= " - {$item->gramage}g";
            }

            $message .= "\nR$ " . number_format($item->subtotal, 2, ',', '.');

            if ($item->observations) {
                $message .= "\nObs: {$item->observations}";
            }
        }

        $message .= "\n\n*Subtotal:* R$ " . number_format($this->subtotal, 2, ',', '.');
        $message .= "\n*Entrega:* {$this->formatted_delivery_fee}";

        if ($this->discount > 0) {
            $message .= "\n*Desconto:* -R$ " . number_format($this->discount, 2, ',', '.');
        }

        $message .= "\n*Total:* R$ " . number_format($this->total, 2, ',', '.');

        if ($this->payment_method) {
            $paymentLabel = match ($this->payment_method) {
                'money' => 'Dinheiro',
                'pix' => 'PIX',
                'card' => 'Cartão',
                'transfer' => 'Transferência',
                default => $this->payment_method,
            };

            $message .= "\n*Pagamento:* {$paymentLabel}";

            if ($this->payment_method === 'money' && $this->change_for) {
                $message .= "\n*Troco para:* R$ " . number_format($this->change_for, 2, ',', '.');
            }
        }

        if ($this->admin_notes) {
            $message .= "\n\n*Observações:* {$this->admin_notes}";
        }

        return $message;
    }

    /**
     * Informa se o pedido ainda pode ser cancelado pelo fluxo operacional.
     */
    public function canBeCanceled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Informa se o pedido ainda aceita transicoes de status.
     */
    public function canChangeStatus(): bool
    {
        return $this->status !== self::STATUS_CANCELED;
    }
}
