<?php
	error_reporting(0);
	ini_set('display_errors', 'Off');

	include_once(__DIR__.'/../include/helper.php');
	include_once(__DIR__."/../class/c_TanController.php");
	include_once(__DIR__."/../class/c_MailController.php");
	include_once(__DIR__."/../class/c_TransactionController.php");
	include_once(__DIR__."/../class/c_DataAccess.php");
	include_once(__DIR__."/../class/c_UserController.php");
	include_once(__DIR__."/../class/c_EmployeeController.php");

	include_once(__DIR__.'/../include/TransferException.php');
	include_once(__DIR__.'/../include/InvalidInputException.php');
	include_once(__DIR__."/../include/InvalidSessionException.php");
	include_once(__DIR__.'/../include/IsActiveException.php');
	include_once(__DIR__.'/../include/SendEmailException.php');
	include_once(__DIR__.'/../include/TimeServerException.php');
	include_once(__DIR__.'/../include/TooManyInvalidTansException.php');

	include_once(__DIR__.'/../include/crypt.php'); 

	include_once(__DIR__.'/../include/phpmailer/class.smtp.php');
	require(__DIR__.'/../include/phpmailer/class.phpmailer.php');

	include_once(__DIR__.'/../include/fpdf/fpdf.php');
	include_once(__DIR__.'/../include/fpdi/FPDI_Protection.php');




	define( "DB_NAME", "mysql:host=localhost;dbname=mybank" );
	define( "DB_USER", "mybankRoot" );
	define( "DB_PASS", "74VKrexSYk8B6g" );
	
	$SECURITY_QUESTIONS = array("What is the first name of the person you first kissed?",
	"What is the last name of the teacher who gave you your first failing grade?",
	"What is the name of the place your wedding reception was held?",
	"In what city or town did you meet your spouse/partner?",
	"What was the make and model of your first car?" );
?>
