<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\LowStockDetectedNotification;
use App\Services\Inventory\LowStockAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class ProcessLowStockAlertsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(LowStockAlertService $service): void
    {
        $products = $service->detectLowStockProducts();

        $service->resolveRecoveredAlerts($products->pluck('id')->all());

        if ($products->isEmpty()) {
            return;
        }

        $permission = (string) config('stockflow.low_stock_alerts.recipients_permission', 'products.view');

        $recipients = User::query()
            ->permission($permission)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($products as $product) {
            [$alert, $shouldNotify] = $service->activateAlert($product);

            if (!$shouldNotify) {
                continue;
            }

            Notification::send($recipients, new LowStockDetectedNotification($alert));

            $alert->update([
                'last_notified_at' => now(),
            ]);
        }
    }
}
