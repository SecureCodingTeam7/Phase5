<?php
	//error_reporting(E_ERROR);
	//ini_set('display_errors', 1);

	define( "DB_NAME", "mysql:host=localhost;dbname=mybank" );
	define( "DB_USER", "root" );
	define( "DB_PASS", "samurai" );
	
	$SECURITY_QUESTIONS = array("What is the first name of the person you first kissed?",
				"What is the last name of the teacher who gave you your first failing grade?",
				"What is the name of the place your wedding reception was held?",
				"In what city or town did you meet your spouse/partner?",
				"What was the make and model of your first car?" );
?>
