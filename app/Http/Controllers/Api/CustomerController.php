<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use \Hash;
use \Auth;

class CustomerController extends Controller
{
    
    

    public function register(Request $request)
    {
        $rules = [
            'email'    => 'required|email|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'phone'    => 'required|unique:users|numeric|digits:10',
        ];

        $validation = \Validator::make( $request->all(), $rules );
    
        if( $validation->fails() ) {
            return redirect()->back()->with('error',$validation->errors()->first());
        }
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->address = $request->address;
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json([
            'msg' => 'User is registerd'
        ],200);

    }

    public function login(Request $request)
    {
        $user = User::where('email',$request->email)->first();
        if($user){
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('MyApp')->accessToken;
                $user->api_token = $token;
                $user->device_token = $request->device_token;
                $user->save();
                Auth::login($user, true);
                return response()->json([
                    'token' => $token
                ],200);
            }
        }
        return response()->json([
            'error' => 'Invalid email or password'
        ],500);
    }

    public function profile(Request $request)
    {
        $user = Auth::User();
        return response()->json([
            'user' => $user
        ],200);
    }

    public function update_profile(Request $request)
    {

        $rules = [
            'profile_pic'    => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:500',
        ];

        $validation = \Validator::make( $request->all(), $rules );
    
        if( $validation->fails() ) {
            return redirect()->back()->with('error',$validation->errors()->first());
        }
           
        $user = Auth::User();
        if($request->hasFile('profile_pic')) {
            $file= $request->file('profile_pic');
            $allowedfileExtension=['JPEG','jpg','png'];
            $extension = $file->getClientOriginalExtension();
            $check = in_array($extension,$allowedfileExtension);
            // if($check){
                $file_path = public_path('/images/customer/profile/'.$user->profile_pic);
                if(file_exists($file_path) && $user->profile_pic != '')
                {
                    unlink($file_path);
                }
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $filename = substr(str_shuffle(str_repeat($pool, 5)), 0, 12) .'.'.$extension;
                $path = $file->move(public_path('/images/customer/profile'), $filename);
                $user->profile_pic = $filename;
            // }
        }
        $user->save();
        return response()->json([
            'msg' => 'Profile picture updated'
        ],200);
    }
    
}