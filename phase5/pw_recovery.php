<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
include_once(__DIR__."/header.php");
session_start();

if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    // thats okay
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	// thats okay
} else {
	// user already logged in
	if($_SESSION['user_level']) {
		header("Location: employee/approve.php");
	} else {
		header("Location: account/index.php");
	}
	die();
}


if( isset($_GET['email']) && isset($_GET['id']) ) {
	
	$user = new User();
	$user->getUserDataFromEmail($_GET['email']);
	
	if( isset($_POST['password'])) {
		
		if($user->doPwRecovery($_GET['id'], $_POST)) {
			echo "Thank you, your password was changed successfully.<br /><a href=\"login.php\">Go to Sign in</a>.";
		}
		
		else {
			echo "We could not verify your answer. Please try again or contact customer support.<br /><a href=\"login.php\">Go to Sign in</a>.";
		}
	
		die();
	}

	$success = false;
	if($user->email) {
		// if we found the mail it is a valid user
		$success = $user->checkPwRecoveryId($_GET['id']);
	}?>
	
	<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
				
			
				<div id="main">
					<p>
					<?php 
					if($success) {
					?>
					
					<form method="post" action="" class="pure-form pure-form-aligned">
						<fieldset>
						
							<div class="pure-control-group">
								<label for="sec_q_number">Security Question:</label>
								<label name="sec_q_number" id="sec_q_number"><?php echo $SECURITY_QUESTIONS[$user->securityQuestionNumber] ?></label>
							</div>
							
							
							<div class="pure-control-group">
								<label for="sec_q_answer">Answer</label>
								<input name="sec_q_answer" id="sec_q_answer" type="text" placeholder="Answer" required>
							</div>
							
							<div class="pure-control-group">
								<label for="password">New Password</label>
								<input name="password" id="password" type="password" placeholder="***********" onkeyup="check_pw()" required>
								<b id=password_info></b>
							</div>
							
							<div class="pure-control-group">
								<label for="confirm_password">Confirm Password</label>
								<input name="confirm_password" id="confirm_password" type="password" placeholder="***********" onkeyup="check_pw()" required>
								<b id=confirm_password_info></b>
							</div>
							
							<div class="pure-controls">
								<button id="SendButton" type="submit" name="recoverPassword" class="pure-button pure-button-primary" onclick="setTimeout(disableFunction, 1);">Submit</button>
							</div>
						
						</fieldset>
					</form>
						
					<?php
					} else {
						echo "Something went wrong, please try again later!";
					}?>
					</p>
				</div> <!-- main -->
			</div><!-- content -->
		</body>
	</html>

<?php 
} else if( !(isset( $_POST['recoverPassword'] ) ) ) { ?>
	<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
				
			
				<div id="main">
					<p>If you changed your mind, <a href="login.php">Click here</a> to go back to login.<br /><br /></p>
					<form method="post" action="" class="pure-form pure-form-aligned">
						<fieldset>
							<div class="pure-control-group">
								<label for="email">Email</label>
								<input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
							</div>
					
							<div class="pure-controls">
								<button id="SignInButton" onclick="setTimeout(disableFunction, 1)" type="submit" name="recoverPassword" class="pure-button pure-button-primary">Recover Password</button>
							</div>
						</fieldset>
					</form>	
				</div><!-- main -->
			</div><!-- content -->
		</body>
	</html>

<?php 
} else {
	
	$user = new User();
	
	$user->getUserDataFromEmail($_POST['email']);
	
	if($user->email) {
		// if we found the mail it is a valid user
		$user->sendPwRecoveryMail();
	} else {
+	// timeout for 3 seconds, so it's not that easy to guess existing accounts via the processing time.
+	sleep(3);
	}?>
	<!doctype html>
	<html>
		<head>
			<title>Password Recovery | myBank</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<script src="include/jquery-2.1.3.min.js"></script>
			<link href="style/bootstrap.css" rel="stylesheet" />
			<script src="include/bootstrap.min.js"></script>
			<link href="style/pure.css" type="text/css" rel="stylesheet" />
			<link href="style/bootstrap.css" rel="stylesheet">
			<link href="style/style.css" type="text/css" rel="stylesheet" />
		</head>
		
		<body>
			<div id="content">
				
				<?php render_guest_header("Login"); ?>
			
				<div id="main">
					<p>A link to recover your password has been dispatched to the provided email address.</p>
					<p><br /><a href="login.php">Click here</a> to go back to login.</p>
			
				</div><!-- main -->
			</div><!-- content -->
		</body>
	</html>
<?php 
}
?>

<script>
	function disableFunction() {
		document.getElementById("SignInButton").disabled = 'true';
	}

	var pw_info = document.getElementById("password_info")
	var confirm_pw_info = document.getElementById("confirm_password_info")

	var pw_field = document.getElementById("password")
	var confirm_pw_field = document.getElementById("confirm_password")

	var submit_button = document.getElementById("SendButton")
	submit_button.disabled = true

	function check_pw() {
		// lets check if the pw is strong enough
		var pw = pw_field.value
		var lowercase = pw.search("[A-Z]")
		var uppercase = pw.search("[a-z]")
		var number = pw.search("[0-9]")

		if(pw.length < 8 || lowercase == -1 || uppercase == -1 || number == -1) {
			pw_info.textContent = "Password must have at least eight characters, one upper case, one lower case letter and one number"
			submit_button.disabled = true
		} else {
			pw_info.textContent = ""
		}

		if(confirm_pw_field.value != pw_field.value) {
			confirm_pw_info.textContent = "The two passwords do not match!"
			submit_button.disabled = true
		} else {
			confirm_pw_info.textContent = ""
			submit_button.disabled = false
		}
	}
</script>