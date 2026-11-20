<?php
namespace App\Services;
class GowaService
{
 public function sendText(string $phone,string $message): bool
 {
  $base=rtrim((string)env('gowa.baseURL',''),' /');
  $user=(string)env('gowa.username',''); $pass=(string)env('gowa.password','');
  $device=(string)env('gowa.deviceId','billing');
  if($base===''||$user===''||$pass===''||$device===''){log_message('error','GOWA configuration incomplete.');return false;}
  $digits=preg_replace('/[^0-9]/','',$phone);
  if($digits===''){log_message('error','GOWA recipient phone is empty or invalid.');return false;}
  $recipient=str_ends_with($phone,'@s.whatsapp.net')?$phone:$digits.'@s.whatsapp.net';
  $client=service('curlrequest',['timeout'=>25,'connect_timeout'=>8,'http_errors'=>false]);
  try{
   $response=$client->post($base.'/send/message',[
    'auth'=>[$user,$pass],
    'headers'=>['X-Device-Id'=>$device,'Accept'=>'application/json','Content-Type'=>'application/json'],
    'json'=>['phone'=>$recipient,'message'=>$message],
   ]);
   $status=$response->getStatusCode(); $raw=(string)$response->getBody(); $body=json_decode($raw,true);
   $hasError=is_array($body)&&(isset($body['error'])||isset($body['errors'])||(isset($body['code'])&&strtoupper((string)$body['code'])!=='SUCCESS'));
   if($status>=200&&$status<300&&!$hasError){log_message('info','GOWA accepted password reset OTP message for device {device}.',['device'=>$device]);return true;}
   log_message('error','GOWA send failed. HTTP {status}; response {body}',['status'=>$status,'body'=>substr($raw,0,700)]);
  }catch(\Throwable $e){log_message('error','GOWA request error: {message}',['message'=>$e->getMessage()]);}
  return false;
 }
}