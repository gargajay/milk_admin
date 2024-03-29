<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Model\CustomerAddress;
use App\Model\Newsletter;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\WalletHistory;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function address_list(Request $request)
    {
        return response()->json(CustomerAddress::where('user_id', $request->user()->id)->latest()->get(), 200);
    }

    public function add_new_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'road' => $request->road,
            'house' => $request->house,
            'floor' => $request->floor,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('customer_addresses')->insert($address);
        return response()->json(['message' => 'successfully added!'], 200);
    }

    public function update_address(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'road' => $request->road,
            'house' => $request->house,
            'floor' => $request->floor,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('customer_addresses')->where('id',$id)->update($address);
        return response()->json(['message' => 'successfully updated!'], 200);
    }

    public function delete_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (DB::table('customer_addresses')->where(['id' => $request['address_id'], 'user_id' => $request->user()->id])->first()) {
            DB::table('customer_addresses')->where(['id' => $request['address_id'], 'user_id' => $request->user()->id])->delete();
            return response()->json(['message' => 'successfully removed!'], 200);
        }
        return response()->json(['message' => 'No such data found!'], 404);
    }

    public function get_order_list(Request $request)
    {
        $orders = Order::where(['user_id' => $request->user()->id])->get();
        return response()->json($orders, 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $details = OrderDetail::where(['order_id' => $request['order_id']])->get();
        foreach ($details as $det) {
            $det['product_details'] = Helpers::product_data_formatting(json_decode($det['product_details'], true));
        }

        return response()->json($details, 200);
    }

    public function info(Request $request)
    {
       return response()->json($request->user(), 200);
    }

    public function update_profile(Request $request)
    {
        //dd(auth()->user()->id);
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            //'phone' => 'required',
            'phone' => ['required', 'unique:users,phone,'.auth()->user()->id]
        ], [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
            'phone.required' => 'Phone is required!',
            'phone.unique' => translate('Phone must be unique!'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image = $request->file('image');

        if ($image != null) {
            $data = getimagesize($image);
            $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . 'png';
            if (!Storage::disk('public')->exists('profile')) {
                Storage::disk('public')->makeDirectory('profile');
            }
            $note_img = Image::make($image)->fit($data[0], $data[1])->stream();
            Storage::disk('public')->put('profile/' . $imageName, $note_img);
        } else {
            $imageName = $request->user()->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $request->user()->password;
        }

        $userDetails = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'image' => $imageName,
            'password' => $pass,
            'updated_at' => now()
        ];

        User::where(['id' => $request->user()->id])->update($userDetails);

        return response()->json(['message' => 'successfully updated!'], 200);
    }

    public function update_cm_firebase_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cm_firebase_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        DB::table('users')->where('id',$request->user()->id)->update([
            'cm_firebase_token'=>$request['cm_firebase_token']
        ]);

        return response()->json(['message' => 'successfully updated!'], 200);
    }

    public function subscribe_newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $newsLetter = Newsletter::where('email', $request->email)->first();
        if (!isset($newsLetter)) {
            $newsLetter = new Newsletter();
            $newsLetter->email = $request->email;
            $newsLetter->save();

            return response()->json(['message' => 'Successfully subscribed'], 200);

        } else {
            return response()->json(['message' => 'Email Already exists'], 400);
        }
    }

    public function remove_account(Request $request): JsonResponse
    {
        $customer = User::find($request->user()->id);
        if(isset($customer)) {
            Helpers::file_remover('profile/', $customer->image);
            $customer->delete();

        } else {
            return response()->json(['status_code' => 404, 'message' => translate('Not found')], 200);
        }

        $conversations = Conversation::where('user_id', $customer->id)->get();
        foreach ($conversations as $conversation){
            if ($conversation->checked == 0){
                $conversation->checked = 1;
                $conversation->save();
            }
        }

        return response()->json(['status_code' => 200, 'message' => translate('Successfully deleted')], 200);
    }


     // wallet work 


     

     public function  walletList(Request $request)
     {
         
        return response()->json(WalletHistory::where('user_id', $request->user()->id)->latest()->get(), 200);

 
 
     }


     public function addMoney(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'amount' => 'required',
             'transaction_id' => 'required'
         ]);
 
         if ($validator->fails()) {
             return response()->json(['errors' => Helpers::error_processor($validator)], 403);
         }
 
         $user = Auth::user();
          $wallet =  new WalletHistory();
 
          $wallet->user_id = $user->id;
          $wallet->amount = $request->amount;
          $wallet->transaction_id = $request->transaction_id;
          $wallet->type_id = WalletHistory::TYPE_ADDED;
          $wallet->info = 'Added new balance to wallet  '.$request->amount;
 
          if($wallet->save())
          {
             $key = $wallet->id.$wallet->user_id."579";
             $wallet->verifyToken = base64_encode($key);
             $wallet->save();
             return response()->json(['message' => 'Added balance sucessfully'], 200);
 
          }else{
             return response()->json(['message' => $wallet->error], 400);
 
          }
 
 
 
     }
 
 
     // use wallet blance
 
     public function useMoney(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'amount' => 'required',
             'transaction_id' => 'required'
         ]);
 
         if ($validator->fails()) {
             return response()->json(['errors' => Helpers::error_processor($validator)], 403);
         }
 
          $user = Auth::user();
          $wallet =  new WalletHistory();
 
          $balance =  $wallet->walletBalance();
 
          if($request->amount>$balance){
             return response()->json(['message' => "Wallet balance is low then order amount"], 403);
 
          }
 
          $wallet->user_id = $user->id;
          $wallet->amount = - $request->amount;
          $wallet->transaction_id = $request->transaction_id;
          $wallet->type_id = WalletHistory::TYPE_USED;
          $wallet->info = ' balance withdraw from  wallet  '.$request->amount;
 
          if($wallet->save())
          {
             $key = $wallet->id.$wallet->user_id."579";
             $wallet->verifyToken = base64_encode($key);
             $wallet->save();
             return response()->json(['message' => 'balance withdraw sucessfully'], 200);
 
          }else{
             return response()->json(['message' => $wallet->error], 200);
 
          }
 
     }

   /* public function unsubscribe_topic(Request $request)
    {
        $user = User::where('id',$request->user()->id)->first();
        'https://iid.googleapis.com/iid/v1/'. $user->cm_firebase_token .'/rel/topics/'. $request['topic'];
        return response()->json(['status_code' => 200, 'message' => translate('Unsubscribed')], 200);
    }*/









}
