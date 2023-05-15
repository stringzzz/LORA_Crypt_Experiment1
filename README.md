# LORA_Crypt_Experiment1

While it does serve as an encryption tool for storing files, one of the main reasons 
 for testing the setup was to make a login system that throws in an extra hoop for the attacker
 to jump through. Neither the password or the password hash are stored in the system, instead
 an attacker would have to go through an extra layer by having to decrypt a file every time 
 they want to try a password. While 8192 bytes is really not a big chore to do this, there
 could easily be more control on the size of the '(username)_file.LORA' file, where if it
 was much larger to the point where decrypting it really slowed down, it would prove difficult
 to crack the password in a reasonable amount of time.
 
 Again, this is purely experimental, at some point I will test some different attacks on it.
 
 
 ########### LORA_Registration.php ############
 
1. Get username and password (Check for existing username)
2. Generate PRNG crypt file, 8192 bytes
3. Get md5 hash of crypt file
4. Using the md5 hash of the user's password as the key, encrypt the crypt file
5. Create locker directory with username
6. Store the crypt file as '(username)_file.LORA' in the locker
7. Store the unencrypted crypt file hash as '(username)_file.HASH
 
 Setup complete

############# LORA_Crypt.php ##############

1. Get the username, check if a locker for it exists
2. Get password, md5 hash it
3. Get the '(username)_file.HASH' contents
4. Get the contents of the '(username)_file.LORA' crypt file
5. Using the hash of the password as key, attempt decrypt of the crypt file
6. Compare the md5 hash of the resulting plaintext with the hash from (username)_file.HASH contents
7. If match, login successful, prompt user for action:

8a (encrypt). Encrypt all unencrypted files in locker, excluding the .HASH file

8b (decrypt). Decrypt all the encrypted files in the locker, excluding the '(username)_file.LORA' file

############ Version 0.02 ##########################

Added feature to be able to encrypt/decrypt the files in the locker one file at a time.

################ Brute Force Time Test Experiment 1 ##################

Added a directory showing a test done to see how long it would take to brute force attack a password in the LORA_Crypt system
with a larger 'crypt file' size, in comparison to brute forcing the same password in an ordinary password hash login system.
The full results are in the 'Test_Results.txt' file in the directory, but a sneak peak is that the LORA_Crypt system
took several times longer to brute force.
