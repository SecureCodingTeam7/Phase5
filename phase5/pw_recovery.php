<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
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
		if($user->doPwRecovery($_GET['id'], $_POST))
			echo "Success, your password was set to the newly entered one!";
		else
			echo "Something went wrong, please try again later!";
			
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
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
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
			
		</div>
		</div>
	</div>
</body>
</html>

<?php 
} else if( !(isset( $_POST['recoverPassword'] ) ) ) { ?>
<!doctype html>
<html>
<head>
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>Remember it again? <a href="login.php">Click here</a> to login.</p>
			<form method="post" action="" class="pure-form pure-form-aligned">
		    <fieldset>
		        <div class="pure-control-group">
		            <label for="email">Email</label>
		            <input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
		        </div>
		
		        <div class="pure-controls">
		            <button id="SignInButton" onclick="setTimeout(disableFunction, 1)" type="submit" name="recoverPassword" class="pure-button pure-button-primary">Send new Password</button>
		        </div>
		        
		        
		    </fieldset>
			</form>
			
			


	    

		</div>
		</div>
	</div>
</body>
</html>

<?php 
} else {
	
	$user = new User();
	
	$user->getUserDataFromEmail($_POST['email']);
	
	if($user->email) {
		// if we found the mail it is a valid user
		$user->sendPwRecoveryMail();
	}
	else {
		// timeout for 3 seconds, so it's not that easy to guess existing accounts via the processing time.
		sleep(3);
	}?>
	
	<!doctype html>
<html>
<head>
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>A link to recover you password has been sent to the provided email address!</p>
			
		</div>
		</div>
	</div>
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
