<?php
$cert = "c:/laragon/etc/ssl/laragon.crt";
$key = "c:/laragon/etc/ssl/laragon.key";

echo "Checking cert file: " . (file_exists($cert) ? "EXISTS" : "NOT FOUND") . "\n";
echo "Checking key file: " . (file_exists($key) ? "EXISTS" : "NOT FOUND") . "\n";

if (file_exists($cert)) {
    echo "Cert readable: " . (is_readable($cert) ? "YES" : "NO") . "\n";
}
if (file_exists($key)) {
    echo "Key readable: " . (is_readable($key) ? "YES" : "NO") . "\n";
}

$ch = curl_init("https://127.0.0.1:8090/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);

if ($response === false) {
    echo "Curl error: " . curl_error($ch) . "\n";
} else {
    echo "Curl success! Response: " . substr($response, 0, 100) . "\n";
}
curl_close($ch);
