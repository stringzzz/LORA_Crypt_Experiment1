<?php

/*

LORA Crypt 2 (LOckeR Automator Version 2.01)
Created by stringzzz, Ghostwarez Co.
Project Completion Date: 01-08-2024

Encrypt or decrypt files in the locker folder created with 'LORA2_Registration.php', 
 by logging in with your username and password

1. Get username and password (Check for existing username)
2. Generate PRNG crypt file, ~1024 kilobytes (~1MB)
3. Get sha256 hash of crypt file
4. Using the sha256 hash of the user's password as the key, encrypt the crypt file
5. Create locker directory with username
6. Store the crypt file as '(username)_file.LORA2' in the locker
7. Store the unencrypted crypt file hash as '(username)_file.HASH
Setup complete

*/

//Get username, check if exists
$username = readline("New Username: ");
foreach (array_diff(scandir(dirname(__file__)), array('.', '..')) as $file) {
	if (str_contains($file, $username . "_locker")) {
		die("Duplicate username, try again.\n");
	}
}
echo "New username valid.\n";

//Get and confirm password
$password = readline("New password: ");
$password2 = readline("Confirm the new password: ");
if ($password != $password2) {
	die("Passwords do not match.\n");
}
echo "New password valid.\n";

//Generate the data used to encrypt for the (username)_file.LORA2 file
$crypt_file_string = "";
//2^20 byte crypt file, ~1MB
for ($n = 0; $n < 1048576; $n++) {
	$crypt_file_string .= rand(0, 255);
}
$crypt_hash = hash("sha256", $crypt_file_string, true, []);

//Set up AES-256-CBC for encrypting the (username)_file.LORA2 file data, 
// with the sha256 hash of the password as the key
$method = 'AES-256-CBC';
$key = hash("sha256", $password, true, []);
$length = openssl_cipher_iv_length($method);
$iv = openssl_random_pseudo_bytes($length);

//Encrypt
$crypt_file_ciphertext = openssl_encrypt($crypt_file_string, $method, $key, OPENSSL_RAW_DATA, $iv);
$crypt_file_ciphertext = base64_encode($crypt_file_ciphertext) . '|' . base64_encode($iv);

//Create the (username)_locker directory, add (username)_file.LORA2 file into it
mkdir($username . "_locker");
$crypt_file = fopen($username . "_locker/" . $username . "_file.LORA2", "w") or die("Unable to open " . $username . "_locker/" . $username . "_file.LORA2\n");
fwrite($crypt_file, $crypt_file_ciphertext);
fclose($crypt_file);
echo $username . "_locker directory created.\n" . $username . "_file.LORA2 file successfully created.\n";

//Add the (username)_file.HASH file to the new user's locker directory
$crypt_hash_file = fopen($username . "_locker/" . $username . "_file.HASH", "w") or die("Unable to open " . $username . "_locker/" . $username . "_file.HASH\n");
fwrite($crypt_hash_file, $crypt_hash);
fclose($crypt_hash_file);
echo $username . "_file.HASH file successfully created.\n";
echo "New username account creation complete.\n";

?>
