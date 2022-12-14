<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\DeliveryMan;
use App\Model\DMReview;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

class DeliveryManController extends Controller
{
    public function index()
    {
        return view('admin-views.delivery-man.index');
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if($request->has('search'))
        {
            $key = explode(' ', $request['search']);
            $delivery_men = DeliveryMan::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }else{
            $delivery_men = new DeliveryMan;
        }
        $delivery_men = $delivery_men->latest()->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.delivery-man.list', compact('delivery_men','search'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $delivery_men = DeliveryMan::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%")
                    ->orWhere('identity_number', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('admin-views.delivery-man.partials._table', compact('delivery_men'))->render()
        ]);
    }

    public function reviews_list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if($request->has('search'))
        {
             $key = explode(' ', $request['search']);
             $delivery_men = DeliveryMan::where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('f_name', 'like', "%{$value}%")
                                ->orWhere('l_name', 'like', "%{$value}%");
                        }
            })->pluck('id')->toArray();
            $reviews = DMReview::with(['delivery_man', 'customer'])->whereIn('delivery_man_id',$delivery_men);
            $query_param = ['search' => $request['search']];
        }else
        {
            $reviews = DMReview::with(['delivery_man', 'customer']);
        }
        $reviews = $reviews->latest()->paginate(Helpers::getPagination())->appends($query_param);
        return view('admin-views.delivery-man.reviews-list', compact('reviews','search'));
    }

    public function preview($id)
    {
        $dm = DeliveryMan::with(['reviews'])->where(['id' => $id])->first();
        $reviews = DMReview::where(['delivery_man_id' => $id])->latest()->paginate(Helpers::getPagination());
        return view('admin-views.delivery-man.view', compact('dm', 'reviews'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'f_name' => 'required|max:100',
            'email' => 'required|regex:/(.+)@(.+)\.(.+)/i|unique:delivery_men',
            'phone' => 'required|unique:delivery_men',
            'password' => 'required|min:8',
            'password_confirmation' => 'required_with:password|same:password|min:8'
        ], [
            'f_name.required' => translate('First name is required!'),
            'email.required' => translate('Email is required!'),
            'email.unique' => translate('Email must be unique!'),
            'phone.required' => translate('Phone is required!'),
            'phone.unique' => translate('Phone must be unique!'),
        ]);

        if ($request->has('image')) {
            $image_name = Helpers::upload('delivery-man/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload('delivery-man/', 'png', $img);
                array_push($id_img_names, $identity_image);
            }
            $identity_image = json_encode($id_img_names);
        } else {
            $identity_image = json_encode([]);
        }

        $dm = new DeliveryMan();
        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->phone = $request->phone;
        $dm->identity_number = $request->identity_number;
        $dm->identity_type = $request->identity_type;
        $dm->branch_id = $request->branch_id;
        $dm->identity_image = $identity_image;
        $dm->image = $image_name;
        $dm->is_active = 1;
        $dm->password = bcrypt($request->password);
        $dm->save();

        Toastr::success('Delivery man added successfully!');
        return redirect('admin/delivery-man/list');
    }

    public function edit($id)
    {
        $delivery_man = DeliveryMan::find($id);
        return view('admin-views.delivery-man.edit', compact('delivery_man'));
    }

    public function status(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        $delivery_man->is_active = $request->status;
        $delivery_man->save();
        Toastr::success('Delivery man status updated!');
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'f_name' => 'required|max:100',
            'email' => 'required|regex:/(.+)@(.+)\.(.+)/i',
            'password_confirmation' => 'required_with:password|same:password'
        ]);

        $delivery_man = DeliveryMan::find($id);

        if ($delivery_man['email'] != $request['email']) {
            $request->validate([
                'email' => 'required|unique:delivery_men',
            ]);
        }

        if ($delivery_man['phone'] != $request['phone']) {
            $request->validate([
                'phone' => 'required|unique:delivery_men',
            ]);
        }

        if ($request->has('image')) {
            $image_name = Helpers::update('delivery-man/', $delivery_man->image, 'png', $request->file('image'));
        } else {
            $image_name = $delivery_man['image'];
        }

        if ($request->has('identity_image')){
            foreach (json_decode($delivery_man['identity_image'], true) as $img) {
                if (Storage::disk('public')->exists('delivery-man/' . $img)) {
                    Storage::disk('public')->delete('delivery-man/' . $img);
                }
            }
            $img_keeper = [];
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload('delivery-man/', 'png', $img);
                array_push($img_keeper, $identity_image);
            }
            $identity_image = json_encode($img_keeper);
        } else {
            $identity_image = $delivery_man['identity_image'];
        }
        $delivery_man->f_name = $request->f_name;
        $delivery_man->l_name = $request->l_name;
        $delivery_man->email = $request->email;
        $delivery_man->phone = $request->phone;
        $delivery_man->identity_number = $request->identity_number;
        $delivery_man->identity_type = $request->identity_type;
        $delivery_man->branch_id = $request->branch_id;
        $delivery_man->identity_image = $identity_image;
        $delivery_man->image = $image_name;
        $delivery_man->password = strlen($request->password) > 1 ? bcrypt($request->password) : $delivery_man['password'];
        $delivery_man->save();
        Toastr::success(translate('Delivery man updated successfully'));
        return redirect('admin/delivery-man/list');
    }

    public function delete(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        if (Storage::disk('public')->exists('delivery-man/' . $delivery_man['image'])) {
            Storage::disk('public')->delete('delivery-man/' . $delivery_man['image']);
        }

        foreach (json_decode($delivery_man['identity_image'], true) as $img) {
            if (Storage::disk('public')->exists('delivery-man/' . $img)) {
                Storage::disk('public')->delete('delivery-man/' . $img);
            }
        }

        $delivery_man->delete();
        Toastr::success(translate('Delivery man removed!'));
        return back();
    }

    public function export()
    {
        $delivery_man = DeliveryMan::all();
        $storage = [];

        foreach($delivery_man as $dm){

            if ($dm['branch_id'] == 0){
                $branch = 'All Branch';
            }else{
                $branch = $dm->branch ? $dm->branch->name : '';
            }

            $storage[] = [
                'first_name' => $dm['f_name'],
                'last_name' => $dm['l_name'],
                'phone' => $dm['phone'],
                'email' => $dm['email'],
                'identity_type' => $dm['identity_type'],
                'identity_number' => $dm['identity_number'],
                'branch' => $branch,
            ];
        }
        return (new FastExcel($storage))->download('delivery-man.xlsx');
    }
}
