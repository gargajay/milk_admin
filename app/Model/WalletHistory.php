<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    protected $table = "wallet_history";


    protected $casts = [
        'type_id'                => 'integer',
        'amount'                 =>  'float',
        'user_id'                => 'integer',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

  
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    

}
