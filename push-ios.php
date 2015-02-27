<?php

// from http://www.macoscoders.com/2009/05/17/iphone-apple-push-notification-service-apns/
// call: /apns/apns.php?message=Hello%20from%20macoscoders&badge=2&sound=received5.caf

$deviceToken = '58ac8e6f54a83e35b8dd1106da9d1f379537d2fcee95b0c1859a2448b676e6ea';

// Passphrase for the private key (ck.pem file)
$pass = "pass";
// Get the parameters from http get or from command line

$message = $_GET['message'] or $message = $argv[1] or $message = 'Message  sent ' . @date("H:i:s d/M/Y", mktime());
$badge = (int)$_GET['badge'] or $badge = (int)$argv[2] or $badge = 111;
$sound = $_GET['sound'] or $sound = $argv[3] or $sound = 'chime';

// Construct the notification payload
$body = array();
$body['aps'] = array('alert' => $message);
if ($badge)
    $body['aps']['badge'] = $badge;
if ($sound)
    $body['aps']['sound'] = $sound;
/* End of Configurable Items */

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');

// assume the private key passphase was removed.
stream_context_set_option($ctx, 'ssl', 'cafile', 'entrust_2048_ca.cer');
stream_context_set_option($ctx, 'ssl', 'passphrase', $pass);
$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
stream_set_blocking($fp, 1);

if (!$fp) {
    print "Failed to connect $err $errstr\n";
    return;
} else {
    print "Connection OK\n";
}

$payload = json_encode($body);

// request one 
// Build the binary notification
$msg = '';

$msg = $msg . chr(1) . pack('n', 32) . pack('H*', $deviceToken);
$msg = $msg . chr(2) . pack('n', strlen($payload)) . $payload;

$length = 1 + 2 + 32 + 1 + 2 + strlen($payload);

$msg = chr(2) . pack('N', $length) . $msg;

print "Payload: " . $payload . PHP_EOL;
print "sending message :" . bin2hex($msg) . "\n";

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
    echo 'Error, notification not sent' . PHP_EOL;
else
    echo 'notification sent!' . PHP_EOL;

$apple_error_response = fread($fp, 6);

if($apple_error_response) {

    $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);

    echo "RESPONSE: \"" . $error_response['status_code'] . " id: " . $error_response['identifier'] . "\"" . PHP_EOL;
}

fclose($fp);

?>