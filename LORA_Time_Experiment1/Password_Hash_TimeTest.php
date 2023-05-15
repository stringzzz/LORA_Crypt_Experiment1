<?php

/*

For use in testing a 'normal' login system with just a password hash
The password is "5000"

by stringzzz, Ghostwarez Co.
05-14-2023

*/

$password = "5000";
$password_hash = md5($password);

$start_time = time();
for($n = 0; $n <= 5000; $n++) {
	if ($password_hash == md5((string)$n)) {
		$end_time = time();
		break;
	}	
}

echo "Password_Hash_TimeTest:\nStart time\t" . $start_time . "\nEnd time:\t" . $end_time . "\nTotal Time elapsed: " . $end_time - $start_time . "\n";

?>
