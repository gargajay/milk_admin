<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    protected $table = "wallet_history";


    const TYPE_ADDED = 1;
    const TYPE_USED = 2;



    protected $key;

    
    function __construct()
    {
      
    }

    protected static function boot()
    {
        
        parent::boot();


        WalletHistory::creating(function($model) {
            $key =  $model->id.$model->user_id;
            $model->verifyToken = base64_encode($key);
        });
    }


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
