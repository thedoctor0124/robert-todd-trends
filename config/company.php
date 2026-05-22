<?php

return [
    'name' => env('COMPANY_NAME', 'Robert Todd Ltd'),
    'email' => env('COMPANY_EMAIL', 'trends@roberttodds.com'),
    'vat_number' => env('COMPANY_VAT_NUMBER', ''),
    'vat_rate' => (float) env('COMPANY_VAT_RATE', 20),
];
