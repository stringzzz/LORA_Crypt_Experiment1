<?php

/*

LORA Crypt (LOckeR Automator Version 0.01)
Created by stringzzz, Ghostwarez Co.
Project Start Date: 05-13-2023
Project Completion Date: 05-14-2023
Fixed mistake of key size not matching md5 hash size: 06-09-2023

Encrypt or decrypt files in the locker folder created with 'LORA_Registration.php', 
 by logging in with your username and password

1. Get username and password (Check for existing username)
2. Generate PRNG crypt file, ~1024 bytes (~1MB)
3. Get md5 hash of crypt file
4. Using the md5 hash of the user's password as the key, encrypt the crypt file
5. Create locker directory with username
6. Store the crypt file as '(username)_file.LORA' in the locker
7. Store the unencrypted crypt file hash as '(username)_file.HASH
Setup complete

###### Purpose of Experiment ########

While it does serve as an encryption tool for storing files, one of the main reasons 
 for testing the setup was to make a login system that throws in an extra hoop for the attacker
 to jump through. Neither the password or the password hash are stored in the system, instead
 an attacker would have to go through an extra layer by having to decrypt a file every time 
 they want to try a password. While 1024 bytes is really not a big chore to do this, there
 could easily be more control on the size of the '(username)_file.LORA' file, where if it
 was much larger to the point where decrypting it really slowed down, it would prove difficult
 to crack the password in a reasonable amount of time.
 
 Again, this is purely experimental, at some point I will test some different attacks on it. 

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

//Generate the data used to encrypt for the (username)_file.LORA file
$crypt_file_string = "";
//2^20 byte crypt file, ~1MB
for ($n = 0; $n < 1048576; $n++) {
	$crypt_file_string .= rand(0, 255);
}
$crypt_hash = md5($crypt_file_string);

//Set up AES-128-CBC for encrypting the (username)_file.LORA file data, 
// with the md5 hash of the password as the key
$method = 'AES-128-CBC';
$key = md5($password);
$length = openssl_cipher_iv_length($method);
$iv = openssl_random_pseudo_bytes($length);

//Encrypt
$crypt_file_ciphertext = openssl_encrypt($crypt_file_string, $method, $key, OPENSSL_RAW_DATA, $iv);
$crypt_file_ciphertext = base64_encode($crypt_file_ciphertext) . '|' . base64_encode($iv);

//Create the (username)_locker directory, add (username)_file.LORA file into it
mkdir($username . "_locker");
$crypt_file = fopen($username . "_locker/" . $username . "_file.LORA", "w") or die("Unable to open " . $username . "_locker/" . $username . "_file.LORA\n");
fwrite($crypt_file, $crypt_file_ciphertext);
fclose($crypt_file);
echo $username . "_locker directory created.\n" . $username . "_file.LORA file successfully created.\n";

//Add the (username)_file.HASH file to the new user's locker directory
$crypt_hash_file = fopen($username . "_locker/" . $username . "_file.HASH", "w") or die("Unable to open " . $username . "_locker/" . $username . "_file.HASH\n");
fwrite($crypt_hash_file, $crypt_hash);
fclose($crypt_hash_file);
echo $username . "_file.HASH file successfully created.\n";
echo "New username account creation complete.\n";

?>
