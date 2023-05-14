<?php

/*

LORA Crypt (LOckeR Automator Version 0.01)
Created by stringzzz, Ghostwarez Co.
Project Start Date: 05-13-2023
Project Completion Date: 05-14-2023

Encrypt or decrypt files in the locker folder created with 'LORA_Registration.php', by logging in with your username and password

1. Get the username, check if a locker for it exists
2. Get password, md5 hash it
3. Get the '(username)_file.HASH' contents
4. Get the contents of the '(username)_file.LORA' crypt file
5. Using the hash of the password as key, attempt decrypt of the crypt file
6. Compare the md5 hash of the resulting plaintext with the hash from (username)_file.HASH contents
7. If match, login successful, prompt user for action:
8a (encrypt). Encrypt all unencrypted files in locker, excluding the .HASH file
8b (decrypt). Decrypt all the encrypted files in the locker, excluding the '(username)_file.LORA' file

###### Purpose of Experiment ########

While it does serve as an encryption tool for storing files, one of the main reasons 
 for testing the setup was to make a login system that throws in an extra hoop for the attacker
 to jump through. Neither the password or the password hash are stored in the system, instead
 an attacker would have to go through an extra layer by having to decrypt a file every time 
 they want to try a password. While 8192 bytes is really not a big chore to do this, there
 could easily be more control on the size of the '(username)_file.LORA' file, where if it
 was much larger to the point where decrypting it really slowed down, it would prove difficult
 to crack the password in a reasonable amount of time.
 
 Again, this is purely experimental, at some point I will test some different attacks on it.
 
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

//Read in password (Should really be masked on terminal), get hash of password
$password = readline("Password: ");
$key = md5($password);

//Retreive the (username)_file.HASH hash
$crypt_hash = file_get_contents($username . "_locker/" . $username . "_file.HASH");

//Set up to decrypt contents of (username)_file.LORA in AES-256_CBC
$method = 'AES-256-CBC';
$key = md5($password);
$length = openssl_cipher_iv_length($method);
$crypt_file_ciphertext = file_get_contents($username . "_locker/" . $username . "_file.LORA");
list($crypt_file_ciphertext, $iv) = explode('|', $crypt_file_ciphertext);
$iv = base64_decode($iv);
$crypt_file_plaintext = openssl_decrypt($crypt_file_ciphertext, $method, $key, 0, $iv);

//Compare hash of decrypted (username)_file.LORA with (username)_file.HASH
if (md5($crypt_file_plaintext) != $crypt_hash) {
	//No match? Denied!
	die("Invalid password, access denied.\n");
} else {
	//Successful decryption, move along
	echo "Login successful, welcome " . $username . ".\n";
	$quit = false;
	while(!$quit) {
		$user_choice = strtolower(readline("Enter 'encrypt', 'decrypt', or 'quit': "));
		if ($user_choice == 'encrypt') {
			//Encrypt every unencrypted file (No .LORA or .HASH extension) in the (username)_locker directory
			echo "Encrypting unencrypted files belonging to " . $username . ".\n";
			foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
				if (str_contains($file, '.LORA') || str_contains($file, '.HASH')) {
					//Skip encrypted files or .HASH file
					continue;
				}
				else {
					//Encrypt the file, add .LORA extension
					$iv = openssl_random_pseudo_bytes($length);
					$plaintext = file_get_contents($username . "_locker/" . $file);
					$ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
					$ciphertext = base64_encode($ciphertext) . '|' . base64_encode($iv);
					$output_file = fopen($username . "_locker/" . $file, "w") or die("Unable to open " . $username . "_locker/" . $file . " for write.\n");
					fwrite($output_file, $ciphertext);
					fclose($output_file);
					rename($username . "_locker/" . $file, $username . "_locker/" . $file . ".LORA");
				}
			}
			echo "Locker encryption for user " . $username . " complete.\n\n";	
		} else if ($user_choice == 'decrypt') {
			//Decrypt every encrypted file (Except (username)_file.LORA) in the (username)_locker directory
			echo "Decrypting encrypted files belonging to " . $username . ".\n";
			foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
				if (!str_contains($file, '.LORA') || $file == $username . "_file.LORA") {
					//Skip unecrypted files or the (username)_file.LORA file
					continue;
				}
				else {
					//Decrypt file, remove .LORA extension
					$ciphertext2 = file_get_contents($username . "_locker/" . $file);
					list($ciphertext2, $iv2) = explode('|', $ciphertext2);
					$iv2 = base64_decode($iv2);
					$plaintext2 = openssl_decrypt($ciphertext2, $method, $key, 0, $iv2);
					$output_file = fopen($username . "_locker/" . $file, "w") or die("Unable to open " . $username . "_locker/" . $file . " for write.\n");
					fwrite($output_file, $plaintext2);
					fclose($output_file);
					rename($username . "_locker/" . $file, str_replace(".LORA", "", $username . "_locker/" . $file . ".LORA"));
				}
			}
			echo "Locker decryption for user " . $username . " complete.\n\n";	
		} else if ($user_choice == 'quit') {
			echo "Thank you for working with LORA, have a nice day.\n";
			break;
		} else {
			echo "Invalid input: " . $user_choice . ".\n";
		}
	}
}

?>
