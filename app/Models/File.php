<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class File extends Model
{
    protected $table = "files";

    protected $fillable = [
        'transaction_id',
        'files',
        'pages',
        'size',
        'color',
        'price',
        'status',
    ];

   
}
