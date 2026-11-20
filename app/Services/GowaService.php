<?php
namespace App\Services;
class GowaService {
 public function sendText(string $phone,string $message): bool {
  $base=rtrim((string)env('gowa.baseURL',''),' /');
  $user=(string)env('gowa.username',''); $pass=(string)env('gowa.password','');
  $device=(string)env('gowa.deviceId','billing');
  if($base===''||$user===''||$pass===''||$device==='') { log_message('error','GOWA configuration incomplete.'); return false; }
  $client=service('curlrequest',['timeout'=>20,'connect_timeout'=>8,'http_errors'=>false]);
  try {
   $response=$client->post($base.'/send/message',[
    'auth'=>[$user,$pass],
    'headers'=>['X-Device-Id'=>$device,'Accept'=>'application/json'],
    'json'=>['phone'=>$phone,'message'=>$message],
   ]);
   $status=$response->getStatusCode();
   $body=json_decode((string)$response->getBody(),true);
   if($status>=200&&$status<300&&(!isset($body['code'])||$body['code']==='SUCCESS')) return true;
   log_message('error','GOWA send failed. HTTP {status}; response {body}', ['status'=>$status,'body'=>substr((string)$response->getBody(),0,500)]);
  } catch(\Throwable $e){ log_message('error','GOWA request error: {message}',['message'=>$e->getMessage()]); }
  return false;
 }
}