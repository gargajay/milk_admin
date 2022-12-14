<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function add_new(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if($request->has('search'))
        {
            $key = explode(' ', $request['search']);
            $coupons = Coupon::where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('title', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%");
                        }
            });
            $query_param = ['search' => $request['search']];
        }else{
            $coupons = new Coupon;
        }
        $coupons = $coupons->latest()->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.coupon.index', compact('coupons','search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:15|unique:coupons',
            'title' => 'required|max:100',
            'start_date' => 'required',
            'expire_date' => 'required',
            'discount' => 'required'
        ],[
            'expire_date.required' => translate('Expired date is required')
        ]);

        if($request->coupon_type!='first_order') {
            $request->validate([
                'limit' => 'required'
            ]);
        }

        if($request->discount_type!='amount') {
            $request->validate([
                'max_discount' => 'required'
            ]);
        }

        DB::table('coupons')->insert([
            'title' => $request->title,
            'code' => $request->code,
            'limit' => $request->coupon_type!='first_order' ? $request->limit : null,
            'coupon_type' => $request->coupon_type,
            'start_date' => $request->start_date,
            'expire_date' => $request->expire_date,
            'min_purchase' => $request->min_purchase != null ? $request->min_purchase : 0,
            'max_discount' => $request->discount_type != 'amount' ? $request->max_discount : 0,
            'discount' => $request->discount_type == 'amount' ? $request->discount : $request['discount'],
            'discount_type' => $request->discount_type,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Toastr::success(translate('Coupon added successfully!'));
        return back();
    }

    public function edit($id)
    {
        $coupon = Coupon::where(['id' => $id])->first();
        return view('admin-views.coupon.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|max:15|unique:coupons,code,'.$id.',id',
            'title' => 'required|max:100',
            'start_date' => 'required',
            'expire_date' => 'required',
            'discount' => 'required'
        ],[
            'code.required' => translate('Code is required'),
            'code.unique' => translate('Code must be unique'),
        ]);

        if($request->coupon_type!='first_order') {
            $request->validate([
                'limit' => 'required'
            ]);
        }

        if($request->discount_type!='amount') {
            $request->validate([
                'max_discount' => 'required'
            ]);
        }

        DB::table('coupons')->where(['id' => $id])->update([
            'title' => $request->title,
            'code' => $request->code,
            'limit' => $request->coupon_type!='first_order' ? $request->limit : null,
            'coupon_type' => $request->coupon_type,
            'start_date' => $request->start_date,
            'expire_date' => $request->expire_date,
            'min_purchase' => $request->min_purchase != null ? $request->min_purchase : 0,
            'max_discount' => $request->discount_type != 'amount' ? $request->max_discount : 0,
            'discount' => $request->discount_type == 'amount' ? $request->discount : $request['discount'],
            'discount_type' => $request->discount_type,
            'updated_at' => now()
        ]);

        Toastr::success(translate('Coupon updated successfully!'));
        return back();
    }

    public function status(Request $request)
    {
        $coupon = Coupon::find($request->id);
        $coupon->status = $request->status;
        $coupon->save();
        Toastr::success(translate('Coupon status updated!'));
        return back();
    }

    public function delete(Request $request)
    {
        $coupon = Coupon::find($request->id);
        $coupon->delete();
        Toastr::success(translate('Coupon removed!'));
        return back();
    }

    public function show($id)
    {
        $coupon = Coupon::where(['id' => $id])->first();
        $currency = Helpers::currency_symbol();
        //dd($coupon);
        return response()->json(['coupon'=>$coupon, 'currency'=>$currency]);
    }

}
