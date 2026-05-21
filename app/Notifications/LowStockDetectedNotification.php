<?php

namespace App\Notifications;

use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LowStockAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $product = $this->alert->product;

        return [
            'type' => 'low_stock',
            'title' => 'Producto con bajo stock',
            'product_id' => $product?->id,
            'sku' => $product?->sku,
            'product_name' => $product?->name,
            'stock' => $this->alert->stock,
            'min_stock' => $this->alert->min_stock,
            'alert_id' => $this->alert->id,
            'detected_at' => optional($this->alert->last_detected_at)->toIso8601String(),
        ];
    }
}
