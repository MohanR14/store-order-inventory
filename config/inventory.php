<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Products with stock_on_hand less than or equal to this threshold
    | will be returned in low-stock inventory queries.
    |
    */
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];
