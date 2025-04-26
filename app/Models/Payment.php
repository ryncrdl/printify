<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Payment extends Model
{
    protected $table = "payments";

    protected $fillable = [
        'transaction_id',
        'payment_id',
        'attributes',
    ];
   
}
