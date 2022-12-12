<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WalletHistory extends Model
{
    protected $table = "wallet_history";


    const TYPE_ADDED = 1;
    const TYPE_USED = 2;



    protected $key;

    
    


    // protected static function boot()
    // {
    //     parent::boot();
        
    //     WalletHistory::saved(function($model) {
               
            
             
    //       });
    // }

 

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

    static function walletBalance()
    {
        $user = Auth::user();

       $records =  WalletHistory::where('verifyToken','!=', null)->where('user_id',$user->id)->get();


        $bal = 0;
       if(!$records->isEmpty())
       {
            foreach($records as  $record)
            {
               
            $keyset = $record->id.$record->user_id."579";

                if($record->verifyToken == base64_encode($keyset)){

                  
                 $bal = $bal + $record->amount;
                }
                
            }
        }

        return $bal;

    

    }

}
