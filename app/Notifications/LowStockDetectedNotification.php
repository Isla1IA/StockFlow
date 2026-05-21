<?php

namespace App\Notifications;

use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LowStockAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $mailEnabled = (bool) config('stockflow.low_stock_alerts.mail_enabled', false);
        $hasEmail = filled($notifiable->email ?? null);

        if ($mailEnabled && $hasEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->alert->product;
        $prefix = (string) config('stockflow.low_stock_alerts.mail_subject_prefix', '[StockFlow]');

        return (new MailMessage)
            ->subject("{$prefix} Alerta de Stock Bajo")
            ->greeting("Hola {$notifiable->name}, ")
            ->line('Se detectó un producto por debajo del stock mínimo.')
            ->line('Producto: ' . ($product?->name ?? 'N/A'))
            ->line('SKU: ' . ($product?->sku ?? 'N/A'))
            ->line('Stock actual: ' . $this->alert->stock)
            ->line('Stock mínimo: ' . $this->alert->min_stock)
            ->line('Fecha dde detección: ' . optional($this->alert->last_detected_at)->format('Y-m-d H:i:s'))
            ->line('Por favor, revisa el inventario lo antes posible.');
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
