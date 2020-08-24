<?php

include('include.php');

$sql = "SELECT username, id FROM users WHERE id IN (SELECT DISTINCT user_id AS id FROM repositories)"; 
if ($result = Nano_Db::query($sql)) {
	foreach ($result as $user) {
		$username = $user['username'];
		echo "Processing User: {$username}\n";
		$fossil = new Nano_Fossil($user);
		$result = $fossil->rebuildAllRepos();
		if (!$result) {
			echo "Failed!\n";
		}
	}
}

?>
