<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{

    protected $appends = ['product_type','days'];

    const TYPE_ONETIME = 1;
    const TYPE_DAILY = 2;
    const TYPE_CUSTOM = 3;

    const DAY_SUNDAY = 1;
    const DAY_MONDAY = 2;
    const DAY_TUESDAY = 3;
    const DAY_WEDNESSDAY = 4;
    const DAY_THURSDAY = 5;
    const DAY_FRIDAY = 6;
    const DAY_SATURDAY = 7;


    public  function getDays($id = null)
    {
        $list = array(
            self::DAY_SUNDAY => "Sun",
            self::DAY_MONDAY=> "Mon",
            self::DAY_TUESDAY=> "Tue",
            self::DAY_WEDNESSDAY=> "Wed",
            self::DAY_THURSDAY=> "Thu",
            self::DAY_FRIDAY=> "Fri",
            self::DAY_SATURDAY=> "Sat",
        );
        if ($id === null)
            return $list;
        return isset($list[$id]) ? $list[$id] : 'Not Defined';
    }


    public  function getType($id = null)
    {
        $list = array(
            self::TYPE_ONETIME => "Onetime",
            self::TYPE_DAILY=> "Daily",
            self::TYPE_CUSTOM=> "Custom",
        );
        if ($id === null)
            return $list;
        return isset($list[$id]) ? $list[$id] : 'Not Defined';
    }


  public function  getProductTypeAttribute()
  {
    return $this->getType();
  }

  public function  getDaysAttribute()
  {
    return $this->getDays();
  }


    
    


    

    protected $casts = [
        'tax'         => 'float',
        'price'       => 'float',
        'capacity'    => 'float',
        'status'      => 'integer',
        'discount'    => 'float',
        'total_stock' => 'integer',
        'set_menu'    => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function translations()
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function active_reviews()
    {
        return $this->hasMany(Review::class)->where(['is_active' => 1])->latest();
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class)->latest();
    }

    public function rating()
    {
        return $this->hasMany(Review::class)
            ->where('is_active', 1)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    public function all_rating()
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function($query){
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }



}
