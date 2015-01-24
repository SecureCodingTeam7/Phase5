<?php
class TooManyInvalidTansException extends Exception {
	public function errorMessage() {
	$errorMsg = "You entered invalid TANs multiple times, hence your account was disabled to protect your security. Please contact customer support for help.";
	return $errorMsg;
	}
}
?> 