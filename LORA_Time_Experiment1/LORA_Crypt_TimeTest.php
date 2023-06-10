<?php

/*

LORA Crypt TimeTest
Created by stringzzz, Ghostwarez Co.
05-14-2023
Fixed mistake of key size not matching md5 hash size: 06-09-2023


Username and password from the LORA_Registration is:
User: LORA_TimeTest
Pass: 5000

To test how long it takes to bruteforce the password in the LORA_Crypt system from "0" to "5000",
using a ~1MB 'crypt file'

*/

//Check if valid username, existing locker with that username
$username = readline("Username: ");
$user_match = false;
foreach (array_diff(scandir(dirname(__file__)), array('.', '..')) as $file) {
	if (str_contains($file, $username . "_locker")) {
		$user_match = true;
		break;
	}
}
if (!$user_match) {
	die("Invalid username " . $username . "\n");
}

$method = 'AES-128-CBC';
$length = openssl_cipher_iv_length($method);
//Retreive the (username)_file.HASH hash
$crypt_hash = file_get_contents($username . "_locker/" . $username . "_file.HASH");
//Set up to decrypt contents of (username)_file.LORA in AES-128-CBC
$crypt_file_ciphertext = file_get_contents($username . "_locker/" . $username . "_file.LORA");
$start_time = time();
$end_time;
for($n = 0; $n <= 5000; $n++) {
$key = md5((string)$n);

list($crypt_file_ciphertext2, $iv) = explode('|', $crypt_file_ciphertext);
$iv = base64_decode($iv);
$crypt_file_plaintext = openssl_decrypt($crypt_file_ciphertext2, $method, $key, 0, $iv);

//Compare hash of decrypted (username)_file.LORA with (username)_file.HASH
if (md5($crypt_file_plaintext) != $crypt_hash) {
	continue;
} else {
	$end_time = time();
	break;
	}
}

echo "LORA_Crypt_TimeTest:\nStart time\t" . $start_time . "\nEnd time:\t" . $end_time . "\nTotal Time elapsed: " . $end_time - $start_time . "\n";

?>
