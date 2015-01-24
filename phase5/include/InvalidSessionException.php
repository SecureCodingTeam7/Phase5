<?php
class InvalidSessionException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Session invalid: '.$this->getMessage();
		return $errorMsg;
	}
}
?>