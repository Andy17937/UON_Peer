<?php
// 邮件发送与配置工具

require_once __DIR__ . '/../config/database.php';

function getMailSettings() {
	$rows = executeQuery("SELECT setting_key, setting_value FROM system_settings WHERE category = 'mail' AND is_active = 1");
	$cfg = [];
	if ($rows) {
		foreach ($rows as $r) { $cfg[$r['setting_key']] = $r['setting_value']; }
	}
	$defaults = [
		'mail_enabled' => '0',
		'mail_smtp_host' => '',
		'mail_smtp_port' => '587',
		'mail_smtp_secure' => 'tls', // none|ssl|tls
		'mail_auth_method' => 'auto', // auto|login|plain|cram-md5
		'mail_force_from_username' => '0',
		'mail_smtp_username' => '',
		'mail_smtp_password' => '',
		'mail_from_email' => 'no-reply@example.com',
		'mail_from_name' => 'UON Q&A System',
		'mail_alert_recipients' => '', // comma separated
		'mail_alert_levels' => 'medium,high,urgent'
	];
	foreach ($defaults as $k => $v) { if (!isset($cfg[$k])) { $cfg[$k] = $v; } }
	$cfg['mail_enabled'] = ($cfg['mail_enabled'] === '1');
	$levels = array_filter(array_map('trim', explode(',', strtolower($cfg['mail_alert_levels']))));
	$cfg['__levels_array'] = $levels;
	$recipients = array_filter(array_map('trim', explode(',', $cfg['mail_alert_recipients'])));
	$cfg['__recipients_array'] = $recipients;
	return $cfg;
}

function saveMailSettings($data) {
	$allowed = [
		'mail_enabled','mail_smtp_host','mail_smtp_port','mail_smtp_secure','mail_auth_method',
		'mail_force_from_username',
		'mail_smtp_username','mail_smtp_password','mail_from_email','mail_from_name',
		'mail_alert_recipients','mail_alert_levels'
	];
	$saved = 0;
	foreach ($allowed as $key) {
		if (!array_key_exists($key, $data)) { continue; }
		$value = $data[$key];
		if ($key === 'mail_enabled') { $value = $value ? '1' : '0'; }
		if ($key === 'mail_smtp_password' && $value === '') { continue; } // 空密码不覆盖
		$exists = executeQuery("SELECT setting_key FROM system_settings WHERE setting_key = ?", [$key]);
		if ($exists && count($exists) > 0) {
			$result = executeQuery(
				"UPDATE system_settings SET setting_value = ?, description = ?, category = 'mail', updated_at = NOW(), is_active = 1 WHERE setting_key = ?",
				[(string)$value, "Mail configuration: $key", $key]
			);
		} else {
			$result = executeQuery(
				"INSERT INTO system_settings (setting_key, setting_value, description, category, is_active, created_at, updated_at) VALUES (?, ?, ?, 'mail', 1, NOW(), NOW())",
				[$key, (string)$value, "Mail configuration: $key"]
			);
		}
		if ($result) { $saved++; }
	}
	return [ 'success' => $saved > 0, 'saved_count' => $saved ];
}

function sendMailAlert($toList, $subject, $htmlBody, $textBody = '') {
	$cfg = getMailSettings();
	if (!$cfg['mail_enabled']) { return [ 'success' => false, 'error' => 'Mail disabled' ]; }
	$recipients = is_array($toList) ? $toList : array_filter(array_map('trim', explode(',', $toList)));
	if (empty($recipients)) { $recipients = $cfg['__recipients_array']; }
	if (empty($recipients)) { return [ 'success' => false, 'error' => 'No recipients specified' ]; }
	$resultAll = [ 'success' => true, 'details' => [] ];
	foreach ($recipients as $to) {
		$res = smtpSendEmail($cfg, $to, $subject, $htmlBody, $textBody);
		$resultAll['details'][] = [ 'to' => $to, 'result' => $res ];
		if (!$res['success']) { $resultAll['success'] = false; $resultAll['error'] = 'One or more sends failed'; }
	}
	return $resultAll;
}

function encodeHeaderUtf8($text) {
	return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function smtpSendEmail($cfg, $to, $subject, $htmlBody, $textBody = '') {
    $host = trim($cfg['mail_smtp_host']);
    $port = intval($cfg['mail_smtp_port'] ?? 587);
    $secure = strtolower(trim($cfg['mail_smtp_secure'] ?? 'tls'));
    $authMethod = strtolower(trim($cfg['mail_auth_method'] ?? 'auto'));
    $user = trim((string)($cfg['mail_smtp_username'] ?? ''));
    $pass = trim((string)($cfg['mail_smtp_password'] ?? ''));
    $fromEmail = (string)($cfg['mail_from_email'] ?? 'no-reply@example.com');
	$fromName = (string)($cfg['mail_from_name'] ?? 'UON Q&A System');
    // sidecloud: 若程序不支持同时传 username 和 sender，可要求发件人=用户名
    if (!empty($cfg['mail_force_from_username']) && $cfg['mail_force_from_username'] === '1') {
        if (!empty($user) && strpos($user, '@') !== false) {
            $fromEmail = $user;
        }
    }

	if ($secure === 'ssl') { $transport = 'ssl://'; } else { $transport = 'tcp://'; }
	$remote = $transport . $host . ':' . $port;
	$timeout = 30;
	$errno = 0; $errstr = '';
	$fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, stream_context_create([ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false ] ]));
	if (!$fp) { return [ 'success' => false, 'error' => "Connect failed: $errstr ($errno)" ]; }
	stream_set_timeout($fp, $timeout);

	$read = function() use ($fp) { return fgets($fp, 515); };
	$expect = function($code) use ($fp, $read) {
		$line = '';
		$resp = '';
		do { $line = $read(); $resp .= $line; } while ($line !== false && isset($line[3]) && $line[3] === '-');
		if (substr($resp, 0, 3) != (string)$code) { return [false, $resp]; }
		return [true, $resp];
	};
	$write = function($data) use ($fp) { fwrite($fp, $data . "\r\n"); };

	list($ok, $resp) = $expect(220);
	if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'Greeting failed: ' . trim($resp) ]; }

	$ehloHost = $host ?: 'localhost';
	$write('EHLO ' . $ehloHost);
	list($ok, $resp) = $expect(250);
	if (!$ok) { $write('HELO ' . $ehloHost); list($ok2, $resp2) = $expect(250); if (!$ok2) { fclose($fp); return [ 'success' => false, 'error' => 'HELO/EHLO failed: ' . trim($resp2) ]; } }

    if ($secure === 'tls') {
		$write('STARTTLS');
		list($ok, $resp) = $expect(220);
		if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'STARTTLS failed: ' . trim($resp) ]; }
		if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
			fclose($fp); return [ 'success' => false, 'error' => 'TLS negotiation failed' ];
		}
		$write('EHLO ' . $ehloHost); list($ok, $resp) = $expect(250); if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'EHLO after STARTTLS failed: ' . trim($resp) ]; }
	}

    if (!empty($user)) {
        // Choose explicit auth method if provided
        if ($authMethod === 'plain') {
            $token = base64_encode("\0" . $user . "\0" . $pass);
            $write('AUTH PLAIN ' . $token); list($okPlain, $respPlain) = $expect(235);
            if (!$okPlain) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (PLAIN): ' . trim($respPlain) ]; }
        } else if ($authMethod === 'login') {
            $write('AUTH LOGIN'); list($okL, $respL) = $expect(334); if (!$okL) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH LOGIN not accepted: ' . trim($respL) ]; }
            $write(base64_encode($user)); list($okU, $respU) = $expect(334); if (!$okU) { fclose($fp); return [ 'success' => false, 'error' => 'Username rejected: ' . trim($respU) ]; }
            $write(base64_encode($pass)); list($okP, $respP) = $expect(235); if (!$okP) { fclose($fp); return [ 'success' => false, 'error' => 'Password rejected: ' . trim($respP) ]; }
        } else if ($authMethod === 'cram-md5') {
            $write('AUTH CRAM-MD5'); list($okC, $respC) = $expect(334); if (!$okC) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH CRAM-MD5 not accepted: ' . trim($respC) ]; }
            $challenge = base64_decode(trim(substr($respC, 4)));
            $digest = hash_hmac('md5', $challenge, $pass);
            $reply = base64_encode($user . ' ' . $digest);
            $write($reply); list($okCE, $respCE) = $expect(235); if (!$okCE) { fclose($fp); return [ 'success' => false, 'error' => 'CRAM-MD5 rejected: ' . trim($respCE) ]; }
        } else {
            // auto negotiate (LOGIN -> PLAIN -> CRAM-MD5)
        // Try AUTH LOGIN first
        $write('AUTH LOGIN'); list($ok, $resp) = $expect(334);
        if ($ok) {
            $write(base64_encode($user)); list($okU, $respU) = $expect(334);
            if ($okU) {
                $write(base64_encode($pass)); list($okP, $respP) = $expect(235);
                if (!$okP) {
                    // Fallback to AUTH PLAIN
                    $token = base64_encode("\0" . $user . "\0" . $pass);
                    $write('AUTH PLAIN ' . $token); list($okPlain, $respPlain) = $expect(235);
                    if (!$okPlain) {
                        // Fallback to AUTH CRAM-MD5
                        $write('AUTH CRAM-MD5'); list($okCram, $respCram) = $expect(334);
                        if ($okCram) {
                            $challenge = base64_decode(trim(substr($respCram, 4)));
                            $digest = hash_hmac('md5', $challenge, $pass);
                            $reply = base64_encode($user . ' ' . $digest);
                            $write($reply); list($okCramEnd, $respCramEnd) = $expect(235);
                            if (!$okCramEnd) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (CRAM-MD5): ' . trim($respCramEnd) ]; }
                        } else {
                            fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (LOGIN/PLAIN), CRAM-MD5 not offered: ' . trim($respPlain) . ' | prev: ' . trim($respP) ];
                        }
                    }
                }
            } else {
                // LOGIN not continuing, try PLAIN directly
                $token = base64_encode("\0" . $user . "\0" . $pass);
                $write('AUTH PLAIN ' . $token); list($okPlain, $respPlain) = $expect(235);
                if (!$okPlain) {
                    // Try CRAM-MD5
                    $write('AUTH CRAM-MD5'); list($okCram, $respCram) = $expect(334);
                    if ($okCram) {
                        $challenge = base64_decode(trim(substr($respCram, 4)));
                        $digest = hash_hmac('md5', $challenge, $pass);
                        $reply = base64_encode($user . ' ' . $digest);
                        $write($reply); list($okCramEnd, $respCramEnd) = $expect(235);
                        if (!$okCramEnd) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (CRAM-MD5): ' . trim($respCramEnd) ]; }
                    } else {
                        fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (PLAIN), CRAM-MD5 not offered: ' . trim($respPlain) . ' | prev: ' . trim($respU) ];
                    }
                }
            }
        } else {
            // LOGIN not supported? try AUTH PLAIN, then CRAM-MD5
            $token = base64_encode("\0" . $user . "\0" . $pass);
            $write('AUTH PLAIN ' . $token); list($okPlain, $respPlain) = $expect(235);
            if (!$okPlain) {
                $write('AUTH CRAM-MD5'); list($okCram, $respCram) = $expect(334);
                if ($okCram) {
                    $challenge = base64_decode(trim(substr($respCram, 4)));
                    $digest = hash_hmac('md5', $challenge, $pass);
                    $reply = base64_encode($user . ' ' . $digest);
                    $write($reply); list($okCramEnd, $respCramEnd) = $expect(235);
                    if (!$okCramEnd) { fclose($fp); return [ 'success' => false, 'error' => 'AUTH failed (CRAM-MD5): ' . trim($respCramEnd) ]; }
                } else {
                    fclose($fp); return [ 'success' => false, 'error' => 'AUTH not accepted (LOGIN/PLAIN), CRAM-MD5 not offered: ' . trim($resp) . ' / ' . trim($respPlain) ];
                }
            }
        }
        }
    }

	$write('MAIL FROM: <' . $fromEmail . '>'); list($ok, $resp) = $expect(250); if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'MAIL FROM failed: ' . trim($resp) ]; }
	$write('RCPT TO: <' . $to . '>'); list($ok, $resp) = $expect(250); if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'RCPT TO failed: ' . trim($resp) ]; }
	$write('DATA'); list($ok, $resp) = $expect(354); if (!$ok) { fclose($fp); return [ 'success' => false, 'error' => 'DATA not accepted: ' . trim($resp) ]; }

	$boundary = 'bnd_' . bin2hex(random_bytes(8));
	$headers = [];
    $headers[] = 'From: ' . encodeHeaderUtf8($fromName) . ' <' . $fromEmail . '>';
    $headers[] = 'Sender: ' . $fromEmail;
    $headers[] = 'Return-Path: ' . $fromEmail;
	$headers[] = 'To: <' . $to . '>';
	$headers[] = 'Subject: ' . encodeHeaderUtf8($subject);
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
	$headers[] = 'Date: ' . date('r');
	$headers[] = 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>';

	$bodyText = $textBody ?: strip_tags($htmlBody);
	$mime = '';
	$mime .= "--$boundary\r\n";
	$mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
	$mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
	$mime .= chunk_split(base64_encode($bodyText));
	$mime .= "--$boundary\r\n";
	$mime .= "Content-Type: text/html; charset=UTF-8\r\n";
	$mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
	$mime .= chunk_split(base64_encode($htmlBody));
	$mime .= "--$boundary--\r\n";

    // 仅对内容进行点转义，不修改终止符
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $mime;
    $data = preg_replace('/\r\n\./', "\r\n..", $data);
    $write($data);
    // 正确的DATA结束符
    $write('.');
    list($ok, $resp) = $expect(250);
    $write('QUIT');
    fclose($fp);
	if (!$ok) { return [ 'success' => false, 'error' => 'Send failed: ' . trim($resp) ]; }
	return [ 'success' => true ];
}

?>

