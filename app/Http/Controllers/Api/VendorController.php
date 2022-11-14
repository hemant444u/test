<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Licence;
use App\Models\Application;
use App\Models\ApplicationDocument;
use \Auth;

class VendorController extends Controller
{
    
    public function send_otp(Request $request)
    {
        $user = User::where('phone',$request->phone)->where('role','agent')->first();
        if($user){
            // $otp = 1234;
            $otp = rand(1000,9999);
            $user->otp = $otp;
            $user->save();
            $this->send_sms($request->phone,$otp);
            
            return response()->json([
                    'msg' => 'otp sent succussfully'
                ],200);
        }

        return response()->json([
            'error' => 'This number is not registered with us'
        ],500);
    }

    public function register(Request $request)
    {
        $user = User::where('phone',$request->phone)->first();
        if(!$user){
            $user = new User();
            $user->role = 'vendor';
            $user->phone = $request->phone;
        }
        $user->save();

        $this->send_otp($request);
    }

    public function login(Request $request)
    {
        $user = User::where('phone',$request->phone)->where('role','agent')->where('otp',$request->otp)->first();
        if($user){
            // if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('MyApp')->accessToken;
                $user->api_token = $token;
                $user->device_token = $request->device_token;
                $user->save();
                Auth::login($user, true);
                return response()->json([
                    'token' => $token

                ],200);
            // }
        }
        return response()->json([
            'error' => 'Invalid Phone number or otp'
        ],500);
    }

    public function details(Request $request)
    {
        $user = Auth::User();
        return response()->json([
            'user' => $user

        ],200);
    }
    
    public function update_profile(Request $request)
    {
        $user = Auth::User();
        $this->update_profiles($request);
        return response()->json([
            'msg' => 'profile updated',
            'user' => $user

        ],200);
    }
    
    public function get_applications(Request $request)
    {
        $applications = Application::where('agent_id',Auth::User()->id)->with('user','service')->get();
        return response()->json([
            'applications' => $applications

        ],200);
    }
    
    public function get_application_documents(Request $request)
    {
        $app = Application::find($request->application_id);
        $req_docs = $app->application_documents;
        return response()->json([
            'application_documents' => $req_docs

        ],200);
    }
    
    public function upload_document(Request $request)
    {
        $app_docs = ApplicationDocument::where('application_id',$request->application_id)->get();
        foreach($app_docs as $app_doc){
            if($request->hasFile($app_doc->id)) {
                $file= $request->file($app_doc->id);
                $allowedfileExtension=['JPEG','jpg','png'];
                $extension = $file->getClientOriginalExtension();
                $check = in_array($extension,$allowedfileExtension);
                if($check){
                    $file_path = public_path('/images/customers/documents/'.$app_doc->file);
                    
                    $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $filename = substr(str_shuffle(str_repeat($pool, 5)), 0, 12) .'.'.$extension;
                    $path = $file->move(public_path('/images/customers/documents'), $filename);
                    $app_doc->file = $filename;
                }
                $app_doc->save();
            }
        }
        
        $application = Application::find($request->application_id);
        $application->is_document_uploaded = 1;
        $application->save();
        
        $id = $application->user_id;
        $token = User::where('id',$id)->pluck('device_token')->first();
        $title = 'Document Uploaded';
        $body = $application->service->name;
        $this->save_notification($application->user_id,$title,$body);
        $this->send_notification($token,$title,$body);
        
        return response()->json([
            'msg' => 'file uploaded'
        ],200);
    }
    
    public function get_notifications()
    {
        $notifications = Auth::User()->notifications;
        return response()->json([
            'notifications' => $notifications
            ],200);
    }
    
    public function read_notification(Request $request)
    {
        $notification = Notification::find($request->id);
        $notification->status = 1;
        $notification->save();
        return response()->json([
            'message' => 'Notification marked as read'
            ],200);
    }
}
