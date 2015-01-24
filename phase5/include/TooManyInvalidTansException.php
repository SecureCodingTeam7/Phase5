<?php
class TooManyInvalidTansException extends Exception {
	public function errorMessage() {
		$errorMsg = "You entered invalid TANs multiple times, hence your account gets inapproved again. Please contact one of our admins for help.";
		return $errorMsg;
	}
}
?> 
