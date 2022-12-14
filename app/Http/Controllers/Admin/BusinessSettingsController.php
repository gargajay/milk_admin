<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\BusinessSetting;
use App\Model\Currency;
use App\Model\SocialMedia;
use App\Model\TimeSlot;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class BusinessSettingsController extends Controller
{
    public function restaurant_index()
    {
        if (BusinessSetting::where(['key' => 'minimum_order_value'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'minimum_order_value'], [
                'value' => 1,
            ]);
        }

        return view('admin-views.business-settings.restaurant-index');
    }

    public function delivery_index()
    {
        if (BusinessSetting::where(['key' => 'minimum_order_value'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'minimum_order_value'], [
                'value' => 1,
            ]);
        }

        return view('admin-views.business-settings.delivery-fee');
    }

    public function maintenance_mode()
    {
        $mode = Helpers::get_business_settings('maintenance_mode');
        DB::table('business_settings')->updateOrInsert(['key' => 'maintenance_mode'], [
            'value' => isset($mode) ? !$mode : 1
        ]);
        if (!$mode){
            return response()->json(['message' => translate('Maintenance Mode is On.')]);
        }
        return response()->json(['message' => translate('Maintenance Mode is Off.')]);
    }

    public function currency_symbol_position($side)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'currency_symbol_position'], [
            'value' => $side
        ]);
        return response()->json(['message' => 'Symbol position is ' . $side]);
    }

    public function phone_verification_status($status)
    {
        $email_status = DB::table('business_settings')->where('key','email_verification')->first()->value;
        //dd($email_status);

        if ($email_status == 1){
            return response()->json([
                'status' => 0,
                'message' => 'Both email and phone verification can not be active at a time!'
            ]);
        }

        DB::table('business_settings')->updateOrInsert(['key' => 'phone_verification'], [
            'value' => $status
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Phone verification status updated'
        ]);
    }

    public function email_verification_status($status)
    {
        $phone_status = DB::table('business_settings')->where('key','phone_verification')->first()->value;
        //dd($phone_status);

        if ($phone_status == 1){
            return response()->json([
                'status' => 0,
                'message' => 'Both email and phone verification can not be active at a time!'
            ]);
        }

        DB::table('business_settings')->updateOrInsert(['key' => 'email_verification'], [
            'value' => $status
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Email verification status updated'
        ]);
    }

    public function self_pickup_status($status)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'self_pickup'], [
            'value' => $status
        ]);
        return response()->json(['message' => 'Pickup status updated']);
    }

    public function restaurant_setup(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'country'], [
            'value' => $request['country']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'time_zone'], [
            'value' => $request['time_zone'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'time_format'], [
            'value' => $request['time_format']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'currency'], [
            'value' => $request['currency'],
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'decimal_point_settings'], [
            'value' => $request['decimal_point_settings'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'footer_text'], [
            'value' => $request['footer_text'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'pagination_limit'], [
            'value' => $request['pagination_limit'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'minimum_order_value'], [
            'value' => $request['minimum_order_value'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'restaurant_name'], [
            'value' => $request->restaurant_name
        ]);

        $curr_logo = BusinessSetting::where(['key' => 'logo'])->first();
        if ($request->has('logo')) {
            $image_name = Helpers::update('restaurant/', $curr_logo->value, 'png', $request->file('logo'));
        } else {
            $image_name = $curr_logo['value'];
        }

        DB::table('business_settings')->updateOrInsert(['key' => 'logo'], [
            'value' => $image_name,
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'phone'], [
            'value' => $request['phone'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'email_address'], [
            'value' => $request['email'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'address'], [
            'value' => $request['address'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'time_format'], [
            'value' => $request['time_format'],
        ]);

        Toastr::success(translate('Settings updated!'));
        return back();
    }

    public function mail_index()
    {
        return view('admin-views.business-settings.mail-index');
    }

    public function mail_send(Request $request)
    {
        $response_flag = 0;
        try {
            $emailServices = Helpers::get_business_settings('mail_config');

            if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                Mail::to($request->email)->send(new \App\Mail\TestEmailSender());
                $response_flag = 1;
            }
        } catch (\Exception $exception) {
            $response_flag = 2;
        }

        return response()->json(['success' => $response_flag]);
    }

    public function mail_config(Request $request)
    {
        $data = Helpers::get_business_settings('mail_config');

        BusinessSetting::where(['key' => 'mail_config'])->update([
            'value' => json_encode([
                "status" => $data['status'],
                "name"       => $request['name'],
                "host"       => $request['host'],
                "driver"     => $request['driver'],
                "port"       => $request['port'],
                "username"   => $request['username'],
                "email_id"   => $request['email'],
                "encryption" => $request['encryption'],
                "password"   => $request['password'],
            ]),
        ]);
        Toastr::success(translate('Configuration updated successfully!'));

        return back();
    }

    public function mail_config_status($status)
    {
        $data = Helpers::get_business_settings('mail_config');
        $data['status'] = $status == '1' ? 1 : 0;

        BusinessSetting::where(['key' => 'mail_config'])->update([
            'value' => $data,
        ]);
        return response()->json(['message' => 'Mail config status updated']);
    }

    public function payment_index()
    {
        return view('admin-views.business-settings.payment-index');
    }

    public function payment_update(Request $request, $name)
    {

        if ($name == 'cash_on_delivery') {
            $payment = BusinessSetting::where('key', 'cash_on_delivery')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'cash_on_delivery',
                    'value'      => json_encode([
                        'status' => $request['status'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'cash_on_delivery'])->update([
                    'key'        => 'cash_on_delivery',
                    'value'      => json_encode([
                        'status' => $request['status'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'digital_payment') {
            $payment = BusinessSetting::where('key', 'digital_payment')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'digital_payment',
                    'value'      => json_encode([
                        'status' => $request['status'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'digital_payment'])->update([
                    'key'        => 'digital_payment',
                    'value'      => json_encode([
                        'status' => $request['status'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'ssl_commerz_payment') {
            $payment = BusinessSetting::where('key', 'ssl_commerz_payment')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'ssl_commerz_payment',
                    'value'      => json_encode([
                        'status'         => 1,
                        'store_id'       => '',
                        'store_password' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'ssl_commerz_payment'])->update([
                    'key'        => 'ssl_commerz_payment',
                    'value'      => json_encode([
                        'status'         => $request['status'],
                        'store_id'       => $request['store_id'],
                        'store_password' => $request['store_password'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'razor_pay') {
            $payment = BusinessSetting::where('key', 'razor_pay')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'razor_pay',
                    'value'      => json_encode([
                        'status'       => 1,
                        'razor_key'    => '',
                        'razor_secret' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'razor_pay'])->update([
                    'key'        => 'razor_pay',
                    'value'      => json_encode([
                        'status'       => $request['status'],
                        'razor_key'    => $request['razor_key'],
                        'razor_secret' => $request['razor_secret'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'paypal') {
            $payment = BusinessSetting::where('key', 'paypal')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'paypal',
                    'value'      => json_encode([
                        'status'           => 1,
                        'paypal_client_id' => '',
                        'paypal_secret'    => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'paypal'])->update([
                    'key'        => 'paypal',
                    'value'      => json_encode([
                        'status'           => $request['status'],
                        'paypal_client_id' => $request['paypal_client_id'],
                        'paypal_secret'    => $request['paypal_secret'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'stripe') {
            $payment = BusinessSetting::where('key', 'stripe')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'stripe',
                    'value'      => json_encode([
                        'status'        => 1,
                        'api_key'       => '',
                        'published_key' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'stripe'])->update([
                    'key'        => 'stripe',
                    'value'      => json_encode([
                        'status'        => $request['status'],
                        'api_key'       => $request['api_key'],
                        'published_key' => $request['published_key'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($name == 'senang_pay') {
            $payment = BusinessSetting::where('key', 'senang_pay')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key'        => 'senang_pay',
                    'value'      => json_encode([
                        'status'      => 1,
                        'secret_key'  => '',
                        'merchant_id' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'senang_pay'])->update([
                    'key'        => 'senang_pay',
                    'value'      => json_encode([
                        'status'      => $request['status'],
                        'secret_key'  => $request['secret_key'],
                        'merchant_id' => $request['merchant_id'],
                    ]),
                    'updated_at' => now(),
                ]);
            }
        }elseif ($name == 'paystack') {
            $payment = BusinessSetting::where('key', 'paystack')->first();
            if (isset($payment) == false) {
                DB::table('business_settings')->insert([
                    'key' => 'paystack',
                    'value' => json_encode([
                        'status' => 1,
                        'publicKey' => '',
                        'secretKey' => '',
                        'paymentUrl' => '',
                        'merchantEmail' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                DB::table('business_settings')->where(['key' => 'paystack'])->update([
                    'key' => 'paystack',
                    'value' => json_encode([
                        'status' => $request['status'],
                        'publicKey' => $request['publicKey'],
                        'secretKey' => $request['secretKey'],
                        'paymentUrl' => $request['paymentUrl'],
                        'merchantEmail' => $request['merchantEmail'],
                    ]),
                    'updated_at' => now()
                ]);
            }
        } else if ($name == 'bkash') {
            DB::table('business_settings')->updateOrInsert(['key' => 'bkash'], [
                'value' => json_encode([
                    'status' => $request['status'],
                    'api_key' => $request['api_key'],
                    'api_secret' => $request['api_secret'],
                    'username' => $request['username'],
                    'password' => $request['password'],
                ])
            ]);
        } else if ($name == 'paymob') {
            DB::table('business_settings')->updateOrInsert(['key' => 'paymob'], [
                'value' => json_encode([
                    'status' => $request['status'],
                    'api_key' => $request['api_key'],
                    'iframe_id' => $request['iframe_id'],
                    'integration_id' => $request['integration_id'],
                    'hmac' => $request['hmac']
                ])
            ]);
        } else if ($name == 'flutterwave') {
            DB::table('business_settings')->updateOrInsert(['key' => 'flutterwave'], [
                'value' => json_encode([
                    'status' => $request['status'],
                    'public_key' => $request['public_key'],
                    'secret_key' => $request['secret_key'],
                    'hash' => $request['hash']
                ])
            ]);
        } else if ($name == 'mercadopago') {
            DB::table('business_settings')->updateOrInsert(['key' => 'mercadopago'], [
                'value' => json_encode([
                    'status' => $request['status'],
                    'public_key' => $request['public_key'],
                    'access_token' => $request['access_token']
                ])
            ]);
        }
        Toastr::success(translate('payment settings updated!'));
        return back();
    }

    public function currency_index()
    {
        return view('admin-views.business-settings.currency-index');
    }

    public function currency_store(Request $request)
    {
        $request->validate([
            'currency_code' => 'required|unique:currencies',
        ]);

        Currency::create([
            "country"         => $request['country'],
            "currency_code"   => $request['currency_code'],
            "currency_symbol" => $request['symbol'],
            "exchange_rate"   => $request['exchange_rate'],
        ]);
        Toastr::success(translate('Currency added successfully!'));
        return back();
    }

    public function currency_edit($id)
    {
        $currency = Currency::find($id);
        return view('admin-views.business-settings.currency-update', compact('currency'));
    }

    public function currency_update(Request $request, $id)
    {
        Currency::where(['id' => $id])->update([
            "country"         => $request['country'],
            "currency_code"   => $request['currency_code'],
            "currency_symbol" => $request['symbol'],
            "exchange_rate"   => $request['exchange_rate'],
        ]);
        Toastr::success(translate('Currency updated successfully!'));
        return redirect('admin/business-settings/currency-add');
    }

    public function currency_delete($id)
    {
        Currency::where(['id' => $id])->delete();
        Toastr::success(translate('Currency removed successfully!'));
        return back();
    }

    public function terms_and_conditions()
    {
        $tnc = BusinessSetting::where(['key' => 'terms_and_conditions'])->first();
        if ($tnc == false) {
            BusinessSetting::insert([
                'key'   => 'terms_and_conditions',
                'value' => '',
            ]);
        }
        return view('admin-views.business-settings.terms-and-conditions', compact('tnc'));
    }

    public function terms_and_conditions_update(Request $request)
    {
        BusinessSetting::where(['key' => 'terms_and_conditions'])->update([
            'value' => $request->tnc,
        ]);
        Toastr::success(translate('Terms and Conditions updated!'));
        return back();
    }

    public function privacy_policy()
    {
        $data = BusinessSetting::where(['key' => 'privacy_policy'])->first();
        if ($data == false) {
            $data = [
                'key' => 'privacy_policy',
                'value' => '',
            ];
            BusinessSetting::insert($data);
        }
        return view('admin-views.business-settings.privacy-policy', compact('data'));
    }

    public function privacy_policy_update(Request $request)
    {
        BusinessSetting::where(['key' => 'privacy_policy'])->update([
            'value' => $request->privacy_policy,
        ]);

        Toastr::success(translate('Privacy policy updated!'));
        return back();
    }

    public function about_us()
    {
        $data = BusinessSetting::where(['key' => 'about_us'])->first();
        if ($data == false) {
            $data = [
                'key' => 'about_us',
                'value' => '',
            ];
            BusinessSetting::insert($data);
        }
        return view('admin-views.business-settings.about-us', compact('data'));
    }

    public function about_us_update(Request $request)
    {
        BusinessSetting::where(['key' => 'about_us'])->update([
            'value' => $request->about_us,
        ]);

        Toastr::success(translate('About us updated!'));
        return back();
    }

    public function faq()
    {
        $data = BusinessSetting::where(['key' => 'faq'])->first();
        if ($data == false) {
            $data = [
                'key' => 'faq',
                'value' => '',
            ];
            BusinessSetting::insert($data);
        }
        return view('admin-views.business-settings.faq', compact('data'));
    }

    public function faq_update(Request $request)
    {
        //dd($request->all());
        BusinessSetting::where(['key' => 'faq'])->update([
            'value' => $request->faq,
        ]);

        Toastr::success(translate('FAQ updated!'));
        return back();
    }

    public function fcm_index()
    {
        if (BusinessSetting::where(['key' => 'fcm_topic'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'fcm_topic',
                'value' => '',
            ]);
        }
        if (BusinessSetting::where(['key' => 'fcm_project_id'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'fcm_project_id',
                'value' => '',
            ]);
        }
        if (BusinessSetting::where(['key' => 'push_notification_key'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'push_notification_key',
                'value' => '',
            ]);
        }

        if (BusinessSetting::where(['key' => 'order_pending_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'order_pending_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'order_confirmation_msg'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'order_confirmation_msg',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'order_processing_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'order_processing_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'out_for_delivery_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'out_for_delivery_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'order_delivered_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'order_delivered_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'delivery_boy_assign_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'delivery_boy_assign_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'delivery_boy_start_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'delivery_boy_start_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'delivery_boy_delivered_message'])->first() == false) {
            BusinessSetting::insert([
                'key'   => 'delivery_boy_delivered_message',
                'value' => json_encode([
                    'status'  => 0,
                    'message' => '',
                ]),
            ]);
        }

        if (BusinessSetting::where(['key' => 'customer_notify_message'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'customer_notify_message',
                'value' => json_encode([
                    'status' => 0,
                    'message' => '',
                ]),
            ]);
        }

        return view('admin-views.business-settings.fcm-index');
    }

    public function update_fcm(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'fcm_project_id'], [
            'value' => $request['fcm_project_id'],
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'push_notification_key'], [
            'value' => $request['push_notification_key'],
        ]);

        Toastr::success(translate('Settings updated!'));
        return back();
    }

    public function update_fcm_messages(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'order_pending_message'], [
            'value' => json_encode([
                'status'  => $request['pending_status'] == 1 ? 1 : 0,
                'message' => $request['pending_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'order_confirmation_msg'], [
            'value' => json_encode([
                'status'  => $request['confirm_status'] == 1 ? 1 : 0,
                'message' => $request['confirm_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'order_processing_message'], [
            'value' => json_encode([
                'status'  => $request['processing_status'] == 1 ? 1 : 0,
                'message' => $request['processing_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'out_for_delivery_message'], [
            'value' => json_encode([
                'status'  => $request['out_for_delivery_status'] == 1 ? 1 : 0,
                'message' => $request['out_for_delivery_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'order_delivered_message'], [
            'value' => json_encode([
                'status'  => $request['delivered_status'] == 1 ? 1 : 0,
                'message' => $request['delivered_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'delivery_boy_assign_message'], [
            'value' => json_encode([
                'status'  => $request['delivery_boy_assign_status'] == 1 ? 1 : 0,
                'message' => $request['delivery_boy_assign_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'delivery_boy_start_message'], [
            'value' => json_encode([
                'status'  => $request['delivery_boy_start_status'] == 1 ? 1 : 0,
                'message' => $request['delivery_boy_start_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'delivery_boy_delivered_message'], [
            'value' => json_encode([
                'status'  => $request['delivery_boy_delivered_status'] == 1 ? 1 : 0,
                'message' => $request['delivery_boy_delivered_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'customer_notify_message'], [
            'value' => json_encode([
                'status' => $request['customer_notify_status'] == 1 ? 1 : 0,
                'message' => $request['customer_notify_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'returned_message'], [
            'value' => json_encode([
                'status' => $request['returned_status'] == 1 ? 1 : 0,
                'message' => $request['returned_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'failed_message'], [
            'value' => json_encode([
                'status' => $request['failed_status'] == 1 ? 1 : 0,
                'message' => $request['failed_message'],
            ]),
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'canceled_message'], [
            'value' => json_encode([
                'status' => $request['canceled_status'] == 1 ? 1 : 0,
                'message' => $request['canceled_message'],
            ]),
        ]);

        Toastr::success(translate('Message updated!'));
        return back();
    }
    public function map_api_setting()
    {
        return view('admin-views.business-settings.map-api');
    }
    public function map_api_store(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'map_api_key'], [
            'value' => $request['map_api_key'],
        ]);
        Toastr::success(translate('Map API updated successfully'));
        return back();
    }

    //recaptcha
    public function recaptcha_index(Request $request)
    {
        return view('admin-views.business-settings.recaptcha-index');
    }

    public function recaptcha_update(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'recaptcha'], [
            'key' => 'recaptcha',
            'value' => json_encode([
                'status' => $request['status'],
                'site_key' => $request['site_key'],
                'secret_key' => $request['secret_key']
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Toastr::success(translate('Updated Successfully'));
        return back();
    }

    //app_setting_index
    public function app_setting_index()
    {
        return View('admin-views.business-settings.app-setting-index');
    }

    public function app_setting_update(Request $request)
    {
        if($request->platform == 'android')
        {
            DB::table('business_settings')->updateOrInsert(['key' => 'play_store_config'], [
                'value' => json_encode([
                    'status' => $request['play_store_status'],
                    'link' => $request['play_store_link'],
                    'min_version' => $request['android_min_version'],

                ]),
            ]);

            Toastr::success(translate('Updated Successfully for Android'));
            return back();
        }

        if($request->platform == 'ios')
        {
            DB::table('business_settings')->updateOrInsert(['key' => 'app_store_config'], [
                'value' => json_encode([
                    'status' => $request['app_store_status'],
                    'link' => $request['app_store_link'],
                    'min_version' => $request['ios_min_version'],
                ]),
            ]);

            Toastr::success(translate('Updated Successfully for IOS'));
            return back();
        }

        Toastr::error(translate('Updated failed'));
        return back();
    }

    //firebase
    public function firebase_message_config_index()
    {
        return View('admin-views.business-settings.firebase-config-index');
    }

    public function firebase_message_config(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'firebase_message_config'], [
            'key' => 'firebase_message_config',
            'value' => json_encode([
                'apiKey' => $request['apiKey'],
                'authDomain' => $request['authDomain'],
                'projectId' => $request['projectId'],
                'storageBucket' => $request['storageBucket'],
                'messagingSenderId' => $request['messagingSenderId'],
                'appId' => $request['appId'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::firebase_message_config_file_gen();

        Toastr::success(translate('Config Updated Successfully'));
        return back();
    }

    function firebase_message_config_file_gen()
    {
        //configs
        $config=\App\CentralLogics\Helpers::get_business_settings('firebase_message_config');
        $apiKey = $config['apiKey'] ?? '';
        $authDomain = $config['authDomain'] ?? '';
        $projectId = $config['projectId'] ?? '';
        $storageBucket = $config['storageBucket'] ?? '';
        $messagingSenderId = $config['messagingSenderId'] ?? '';
        $appId = $config['appId'] ?? '';

        try {
            $old_file = fopen("firebase-messaging-sw.js", "w") or die("Unable to open file!");

            $new_text = "importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');\n";
            $new_text .= "importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');\n";
            $new_text .= 'firebase.initializeApp({apiKey: "' . $apiKey . '",authDomain: "' . $authDomain . '",projectId: "' . $projectId . '",storageBucket: "' . $storageBucket . '", messagingSenderId: "' . $messagingSenderId . '", appId: "' . $appId . '"});';
            $new_text .= "\nconst messaging = firebase.messaging();\n";
            $new_text .= "messaging.setBackgroundMessageHandler(function (payload) { return self.registration.showNotification(payload.data.title, { body: payload.data.body ? payload.data.body : '', icon: payload.data.icon ? payload.data.icon : '' }); });";
            $new_text .= "\n";

            fwrite($old_file, $new_text);
            fclose($old_file);

        }catch (\Exception $exception) {}

    }

    //social media
    public function social_media()
    {
        // $about_us = BusinessSetting::where('type', 'about_us')->first();
        return view('admin-views.business-settings.social-media');
    }

    public function fetch(Request $request)
    {
        if ($request->ajax()) {
            $data = SocialMedia::orderBy('id', 'desc')->get();
            return response()->json($data);
        }
    }

    public function social_media_store(Request $request)
    {
        try {
            SocialMedia::updateOrInsert([
                'name' => $request->get('name'),
            ], [
                'name' => $request->get('name'),
                'link' => $request->get('link'),
            ]);

            return response()->json([
                'success' => 1,
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'error' => 1,
            ]);
        }

    }

    public function social_media_edit(Request $request)
    {
        $data = SocialMedia::where('id', $request->id)->first();
        return response()->json($data);
    }

    public function social_media_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:social_medias',
        ]);

        if ($validator->errors()->count()>0) {
            return response()->json(['error' => 1]);
        }
        $request->validate([

        ]);

        $social_media = SocialMedia::find($request->id);
        $social_media->name = $request->name;
        $social_media->link = $request->link;
        $social_media->save();
        return response()->json();
    }

    public function social_media_delete(Request $request)
    {
        $br = SocialMedia::find($request->id);
        $br->delete();
        return response()->json();
    }

    public function social_media_status_update(Request $request)
    {
        SocialMedia::where(['id' => $request['id']])->update([
            'status' => $request['status'],
        ]);
        return response()->json([
            'success' => 1,
        ], 200);
    }

    public function main_branch_setup()
    {
        $main_branch = Branch::where(['id' => 1])->first();
        //return $main_branch;
        return view('admin-views.business-settings.main-branch-setup', compact('main_branch'));
    }

    public function delivery_setup_update(Request $request)
    {
        //dd($request->all());
        if($request->delivery_charge == null) {
            $request->delivery_charge = BusinessSetting::where(['key' => 'delivery_charge'])->first()->value;
        }

        DB::table('business_settings')->updateOrInsert(['key' => 'delivery_charge'], [
            'value' => $request->delivery_charge,
        ]);

        if ($request['shipping_status'] == 1) {
            $request->validate([
                'min_shipping_charge' => 'required',
                'shipping_per_km' => 'required',
            ],
                [
                    'min_shipping_charge.required' => translate('Minimum shipping charge is required while shipping method is active'),
                    'shipping_per_km.required' => translate('Shipping charge per Kilometer is required while shipping method is active'),
                ]);
        }
        if($request['min_shipping_charge'] == null) {
            $request['min_shipping_charge'] = Helpers::get_business_settings('delivery_management')['min_shipping_charge'];
        }
        if($request['shipping_per_km'] == null) {
            $request['shipping_per_km'] = Helpers::get_business_settings('delivery_management')['shipping_per_km'];
        }
        DB::table('business_settings')->updateOrInsert(['key' => 'delivery_management'], [
            'value' => json_encode([
                'status'  => $request['shipping_status'],
                'min_shipping_charge' => $request['min_shipping_charge'],
                'shipping_per_km' => $request['shipping_per_km'],
            ]),
        ]);

        Toastr::success(translate('Settings Updated'));
        return back();
    }

    public function social_media_login()
    {
        return view('admin-views.business-settings.social-media-login');
    }

    public function google_social_login($status)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'google_social_login'], [
            'value' => $status
        ]);
        return response()->json(['message' => 'Status updated']);
    }

    public function facebook_social_login($status)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'facebook_social_login'], [
            'value' => $status
        ]);
        return response()->json(['message' => 'Status updated']);
    }

    public function product_setup()
    {
        return view('admin-views.business-settings.product-setup-index');
    }

    public function product_setup_update(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'minimum_stock_limit'], [
            'value' => $request['minimum_stock_limit'],
        ]);

        Toastr::success(translate('Settings updated!'));
        return back();
    }

}
