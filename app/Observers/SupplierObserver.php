<?php

namespace App\Observers;

use App\Models\QtSupplier;
use App\Mail\WelcomeSupplierMail;
use Illuminate\Support\Facades\Mail;

class SupplierObserver
{
    public function created(QtSupplier $supplier): void
    {
        // Questo verrà automaticamente messo in coda
        Mail::to($supplier->email)->send(new WelcomeSupplierMail($supplier));
    }
}
