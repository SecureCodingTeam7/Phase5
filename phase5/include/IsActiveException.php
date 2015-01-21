<?php
class IsActiveException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Account is not active! Please wait until someone approved it.';
		return $errorMsg;
	}
}
?>
