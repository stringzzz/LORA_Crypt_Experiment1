<?php

/*

LORA Crypt 2 (LOckeR Automator Version 2.01)
Created by stringzzz, Ghostwarez Co.
Project Completion Date: 01-08-2024

Encrypt or decrypt files in the locker folder created with 'LORA_Registration.php', by logging in with your username and password

1. Get the username, check if a locker for it exists
2. Get password, sha256 hash it
3. Get the '(username)_file.HASH' contents
4. Get the contents of the '(username)_file.LORA2' crypt file
5. Using the hash of the password as key, attempt decrypt of the crypt file
6. Compare the sha256 hash of the resulting plaintext with the hash from (username)_file.HASH contents
7. If match, login successful, prompt user for action:
8a (encrypt). Encrypt all unencrypted files in locker, excluding the .HASH file
8b (decrypt). Decrypt all the encrypted files in the locker, excluding the '(username)_file.LORA2' file

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
$key = hash("sha256", $password, true, []);

//Retreive the (username)_file.HASH hash
$crypt_hash = file_get_contents($username . "_locker/" . $username . "_file.HASH");

//Set up to decrypt contents of (username)_file.LORA2 in AES-256-CBC
$method = 'AES-256-CBC';
$key = hash("sha256", $password, true, []);
$length = openssl_cipher_iv_length($method);
$crypt_file_ciphertext = file_get_contents($username . "_locker/" . $username . "_file.LORA2");
list($crypt_file_ciphertext, $iv) = explode('|', $crypt_file_ciphertext);
$iv = base64_decode($iv);
$crypt_file_plaintext = openssl_decrypt($crypt_file_ciphertext, $method, $key, 0, $iv);

//Compare hash of decrypted (username)_file.LORA2 with (username)_file.HASH
if (hash("sha256", $crypt_file_plaintext, true, []) != $crypt_hash) {
	//No match? Denied!
	die("Invalid password, access denied.\n");
} else {
	//Successful decryption, move along
	echo "Login successful, welcome " . $username . ".\n";
	$quit = false;
	while(!$quit) {
		echo "Enter:\n'encrypt': Encrypt all unencrypted files\n'encryptone': Encrypt unencrypted files one at a time\n'decrypt': Decrypt all encrypted files\n'decryptone': Decrypt encrypted files one at a time\n'quit': Exit the system\n";
		$user_choice = strtolower(readline(""));
		if ($user_choice == 'encrypt') {
			//Encrypt every unencrypted file (No .LORA2 or .HASH extension) in the (username)_locker directory
			echo "Encrypting unencrypted files belonging to " . $username . ".\n";
			foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
				if (str_contains($file, '.LORA2') || str_contains($file, '.HASH')) {
					//Skip encrypted files or .HASH file
					continue;
				}
				else {
					//Encrypt the file, add .LORA2 extension
					$iv = openssl_random_pseudo_bytes($length);
					$plaintext = file_get_contents($username . "_locker/" . $file);
					$ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
					$ciphertext = base64_encode($ciphertext) . '|' . base64_encode($iv);
					$output_file = fopen($username . "_locker/" . $file, "w") or die("Unable to open " . $username . "_locker/" . $file . " for write.\n");
					fwrite($output_file, $ciphertext);
					fclose($output_file);
					rename($username . "_locker/" . $file, $username . "_locker/" . $file . ".LORA2");
				}
			}
			echo "Locker encryption for user " . $username . " complete.\n\n";	
		} else if ($user_choice == 'encryptone') {
			$user_choice2 = "none";
			while($user_choice2 != 'quit') {
				$unencrypted_files = [];
				$file_count = 0;
				foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
					if (str_contains($file, '.LORA2') || str_contains($file, '.HASH')) {
						//Skip encrypted files or .HASH file
						continue;
					}
					else {
						$unencrypted_files[] = $username . "_locker/" . $file;
						$file_count++;
					}
				}
				if ($file_count != 0) {
					for($i = 0; $i < $file_count; $i++) {
						echo $i . " : " . $unencrypted_files[$i] . "\n";
					}
					$file_choice = readline("Enter the index of the file to encrypt, or 'quit' to exit: ");
					if ($file_choice >= 0 && $file_choice < $file_count) {
						//Encrypt the file, add .LORA2 extension
						$iv = openssl_random_pseudo_bytes($length);
						$plaintext = file_get_contents($unencrypted_files[$file_choice]);
						$ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
						$ciphertext = base64_encode($ciphertext) . '|' . base64_encode($iv);
						$output_file = fopen($unencrypted_files[$file_choice], "w") or die("Unable to open " . $unencrypted_files[$file_choice] . " for write.\n");
						fwrite($output_file, $ciphertext);
						fclose($output_file);
						rename($unencrypted_files[$file_choice], $unencrypted_files[$file_choice] . ".LORA2");
						echo $unencrypted_files[$file_choice] . " now encrypted.\n\n";
						continue;
					} else if (strtolower($file_choice) == 'quit') {
						echo "Exiting single file encryption mode.\n\n";
						break;
					} else {
						echo "Invalid file index " . $file_choice . "\n";
						continue;
					}
				} else {
					echo "No unencrypted files remaining, exiting single file encryption mode...\n\n";
					break;
				}
			}
		} else if ($user_choice == 'decrypt') {
			//Decrypt every encrypted file (Except (username)_file.LORA2) in the (username)_locker directory
			echo "Decrypting encrypted files belonging to " . $username . ".\n";
			foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
				if (!str_contains($file, '.LORA2') || $file == $username . "_file.LORA2") {
					//Skip unecrypted files or the (username)_file.LORA2 file
					continue;
				}
				else {
					//Decrypt file, remove .LORA2 extension
					$ciphertext2 = file_get_contents($username . "_locker/" . $file);
					list($ciphertext2, $iv2) = explode('|', $ciphertext2);
					$iv2 = base64_decode($iv2);
					$plaintext2 = openssl_decrypt($ciphertext2, $method, $key, 0, $iv2);
					$output_file = fopen($username . "_locker/" . $file, "w") or die("Unable to open " . $username . "_locker/" . $file . " for write.\n");
					fwrite($output_file, $plaintext2);
					fclose($output_file);
					rename($username . "_locker/" . $file, str_replace(".LORA2", "", $username . "_locker/" . $file . ".LORA2"));
				}
			}
			echo "Locker decryption for user " . $username . " complete.\n\n";	
		} else if ($user_choice == 'decryptone') {
			$user_choice2 = "none";
			while($user_choice2 != 'quit') {
				$encrypted_files = [];
				$file_count = 0;
				foreach (array_diff(scandir(dirname(__file__) .  "/" . $username . "_locker"), array('.', '..')) as $file) {
				if (!str_contains($file, '.LORA2') || $file == $username . "_file.LORA2") {
					//Skip unencrypted files or the (username)_file.LORA2 file
					continue;
				} else {
					$encrypted_files[] = $username . "_locker/" . $file;
					$file_count++;
					}
				}
				
				if ($file_count != 0) {
					for($i = 0; $i < $file_count; $i++) {
						echo $i . " : " . $encrypted_files[$i] . "\n";
					}
					$file_choice = readline("Enter the index of the file to decrypt, or 'quit' to exit: ");
					
					if ($file_choice >= 0 && $file_choice < $file_count) {
						//Decrypt file, remove .LORA2 extension
						$ciphertext2 = file_get_contents($encrypted_files[$file_choice]);
						list($ciphertext2, $iv2) = explode('|', $ciphertext2);
						$iv2 = base64_decode($iv2);
						$plaintext2 = openssl_decrypt($ciphertext2, $method, $key, 0, $iv2);
						$output_file = fopen($encrypted_files[$file_choice], "w") or die("Unable to open " . $encrypted_files[$file_choice] . " for write.\n");
						fwrite($output_file, $plaintext2);
						fclose($output_file);
						rename($encrypted_files[$file_choice], str_replace(".LORA2", "", $encrypted_files[$file_choice] . ".LORA2"));
						echo $encrypted_files[$file_choice] . " now decrypted. Now sleeping 8 seconds to refresh...\n\n";
						sleep(8);
						continue;
					} else if (strtolower($file_choice) == 'quit') {
						echo "Exiting single file decryption mode.\n\n";
						break;
					} else {
						echo "Invalid file index " . $file_choice . "\n";
						continue;
					}
				} else {
					echo "No encrypted files remaining. Exiting single file decryption mode...\n\n";
					break;
				}
			}
		} else if ($user_choice == 'quit') {
			echo "Thank you for working with LORA2, have a nice day.\n";
			break;
		} else {
			echo "Invalid input: " . $user_choice . ".\n";
		}
	}
}

?>
