<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { http_response_code(401); exit('Please log in to continue.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
$token=(string)($_POST['csrf_token']??'');
if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$token)){http_response_code(403);exit('Security token expired. Refresh and try again.');}
$path=dirname(__DIR__).'/data/content.json';
if(!is_file($path)||!is_readable($path)||!is_writable($path)){http_response_code(500);exit('content.json must be readable and writable by PHP.');}
$data=json_decode((string)file_get_contents($path),true);
if(!is_array($data)||json_last_error()!==JSON_ERROR_NONE){http_response_code(500);exit('content.json contains invalid JSON.');}
function fs($v,int $max):string{if(!is_scalar($v))throw new RuntimeException('Invalid field.');$v=trim((string)$v);if(strlen($v)>$max)throw new RuntimeException('A field exceeds the allowed length.');return $v;}
function frows($key,$kind):array{$rows=$_POST[$key]??[];if(!is_array($rows)||count($rows)>50)throw new RuntimeException('Invalid list or too many entries.');$out=[];foreach($rows as $r){if(!is_array($r))continue;if($kind==='link'){$label=fs($r['label']??'',100);$url=fs($r['url']??'#',500);if($label==='')throw new RuntimeException('Every footer link needs a label.');if(!safeFooterUrl($url))throw new RuntimeException('Invalid footer link URL.');$out[]=['label'=>$label,'url'=>$url];}else{$title=fs($r['title']??'',150);if($title==='')throw new RuntimeException('Every feature needs a title.');$out[]=['icon'=>fs($r['icon']??'',20),'title'=>$title,'description'=>fs($r['description']??'',300)];}}return array_values($out);}
function safeFooterUrl($u):bool{if($u===''||str_starts_with($u,'#')||str_starts_with($u,'/')||str_starts_with($u,'tel:')||str_starts_with($u,'mailto:'))return true;$s=strtolower((string)parse_url($u,PHP_URL_SCHEME));return in_array($s,['http','https'],true)&&filter_var($u,FILTER_VALIDATE_URL)!==false;}
function writeFooterJson($path,$data){$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false)throw new RuntimeException('Could not encode JSON.');$tmp=$path.'.tmp';$fp=fopen($tmp,'wb');if(!$fp)throw new RuntimeException('Could not create temporary JSON file.');if(!flock($fp,LOCK_EX)){fclose($fp);@unlink($tmp);throw new RuntimeException('Could not lock JSON file.');}$n=fwrite($fp,$json.PHP_EOL);fflush($fp);flock($fp,LOCK_UN);fclose($fp);if($n===false||$n<strlen($json)){@unlink($tmp);throw new RuntimeException('Could not write complete JSON.');}if(!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Could not replace content.json. Check permissions.');}}
try{
 $f=$data['footerSection']??[];if(!is_array($f))$f=[];
 $fields=['ctaEyebrow'=>150,'ctaTitle'=>220,'ctaDescription'=>1000,'ctaButton'=>100,'expertTitleHighlight'=>120,'expertTitle'=>120,'expertSubtitle'=>180,'expertDescription'=>1000,'expertButton'=>100,'phone'=>20,'whatsapp'=>20,'logoText'=>50,'logoHighlight'=>70,'brandDescription'=>700,'copyright'=>150,'privacyUrl'=>500];
 foreach($fields as $k=>$max)$f[$k]=fs($_POST[$k]??($f[$k]??''),$max);
 foreach(['phone','whatsapp'] as $k){$digits=preg_replace('/\D+/','',$f[$k]);if(strlen($digits)<10||strlen($digits)>15)throw new RuntimeException('Enter a valid phone number including country code if required.');}
 if(!safeFooterUrl($f['privacyUrl']))throw new RuntimeException('Invalid privacy policy URL.');
 $f['enabled']=isset($_POST['enabled']);$f['mobileBarEnabled']=isset($_POST['mobileBarEnabled']);
 $f['features']=frows('features','feature');$f['quickLinks']=frows('quickLinks','link');$f['exploreLinks']=frows('exploreLinks','link');
 $data['footerSection']=$f;writeFooterJson($path,$data);
 header('Location: footer.php?saved='.rawurlencode('Footer settings saved successfully.'));exit;
}catch(RuntimeException $e){header('Location: footer.php?error='.rawurlencode($e->getMessage()));exit;}
