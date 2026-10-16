<?php

use Illuminate\Support\Facades\Route;

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);

Route::statamic('checkout', 'checkout.index', ['title' => 'Checkout', 'layout' => 'checkout.layout'])
    ->name('checkout');

Route::statamic('checkout/confirmation', 'checkout.confirmation', ['title' => 'Order Confirmation', 'layout' => 'checkout.layout'])
    ->name('checkout.confirmation')
    ->middleware('signed');

// The ready-made views and default styles, on a book's page-like layout.
Route::statamic('ready-made', 'ready-made', ['title' => 'Ready-made reviews']);
