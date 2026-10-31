<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { http_response_code(401); exit('Please log in.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
$token=(string)($_POST['csrf_token']??'');
if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$token)){http_response_code(403);exit('Security token expired. Refresh and try again.');}
$root=dirname(__DIR__);$path=$root.'/data/content.json';
if(!is_file($path)||!is_readable($path)||!is_writable($path)){http_response_code(500);exit('data/content.json must be readable and writable by PHP.');}
$data=json_decode((string)file_get_contents($path),true);if(!is_array($data)){http_response_code(500);exit('Invalid content.json. Restore a valid backup.');}
$allowed=['projects','builders','locations','investment'];$raw=explode(',',(string)($_POST['order']??''));$order=[];foreach($raw as $key){$key=trim($key);if(in_array($key,$allowed,true)&&!in_array($key,$order,true))$order[]=$key;}foreach($allowed as $key)if(!in_array($key,$order,true))$order[]=$key;
$enabled=[];$posted=$_POST['enabled']??[];foreach($allowed as $key)$enabled[$key]=isset($posted[$key])&&$posted[$key]==='1';
$data['homepageLayout']=['order'=>$order,'enabled'=>$enabled];
$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false){http_response_code(500);exit('Could not encode JSON.');}
$tmp=$path.'.tmp';$fp=fopen($tmp,'wb');if(!$fp){http_response_code(500);exit('Could not create temporary file.');}if(!flock($fp,LOCK_EX)){fclose($fp);@unlink($tmp);http_response_code(500);exit('Could not lock JSON file.');}$written=fwrite($fp,$json.PHP_EOL);fflush($fp);flock($fp,LOCK_UN);fclose($fp);if($written===false||$written<strlen($json)||!rename($tmp,$path)){@unlink($tmp);http_response_code(500);exit('Could not save content.json. Check permissions.');}
header('Location: sections.php?saved='.rawurlencode('Homepage section order and visibility saved successfully.'));exit;
