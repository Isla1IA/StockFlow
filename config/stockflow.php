<?php

return [
    'low_stock_alerts' => [
        'mail_enabled' => env('LOW_STOCK_ALERT_MAIL_ENABLED', true),
        'mail_subject_prefix' => env('LOW_STOCK_ALERT_MAIL_SUBJECT_PREFIX', '[StockFlow]'),
        'recipients_permission' => env('LOW_STOCK_ALERT_RECIPIENTS_PERMISSION', 'products.view'),
    ],
];
