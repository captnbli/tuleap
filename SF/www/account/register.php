<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
require "timezones.php";

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
    global $HTTP_POST_VARS, $G_USER;

    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '$HTTP_POST_VARS[form_loginname]'")) > 0) {
	$GLOBALS['register_error'] = "That username already exists.";
	return 0;
    }
    if (!$HTTP_POST_VARS['form_loginname']) {
	$GLOBALS['register_error'] = "You must supply a username.";
	return 0;
    }
    if (!$HTTP_POST_VARS['form_pw']) {
	$GLOBALS['register_error'] = "You must supply a password.";
	return 0;
    }
    if ($HTTP_POST_VARS['form_pw'] != $HTTP_POST_VARS['form_pw2']) {
	$GLOBALS['register_error'] = "Passwords do not match.";
	return 0;
    }
    if (!account_pwvalid($HTTP_POST_VARS['form_pw'])) {
	return 0;
    }
    if (!account_namevalid($HTTP_POST_VARS['form_loginname'])) {
	return 0;
    }
    if (!validate_email($HTTP_POST_VARS['form_email'])) {
	$GLOBALS['register_error'] = ' Invalid Email Address ';
	return 0;
    }
	
    // if we got this far, it must be good
    $confirm_hash = substr(md5($session_hash . $HTTP_POST_VARS['form_pw'] . time()),0,16);

    $result=db_query("INSERT INTO user (user_name,user_pw,unix_pw,windows_pw,realname,email,add_date,"
		     . "status,confirm_hash,mail_siteupdates,mail_va,timezone) "
		     . "VALUES ('$HTTP_POST_VARS[form_loginname]','"
		     . md5($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genunixpw($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genwinpw($HTTP_POST_VARS['form_pw']) . "','"
		     . "$GLOBALS[form_realname]','$GLOBALS[form_email]'," . time() . ","
		     . "'P','" // status
		     . $confirm_hash
		     . "',"
		     . ($GLOBALS['form_mail_site']?"1":"0") . ","
		     . ($GLOBALS['form_mail_va']?"1":"0") . ","
		     . "'".$GLOBALS['timezone']."')");

    if (!$result) {
	exit_error('error',db_error());
    } else {

	$GLOBALS['newuserid'] = db_insertid($result);

	// send mail
	$message = "Thank you for registering on the ".$GLOBALS['sys_name']." web site. In order\n"
	    . "to confirm your registration you must visit the following url: \n\n"
	    . "<http://". $GLOBALS['sys_default_domain'] ."/account/verify.php?confirm_hash=$confirm_hash>\n\n"
	    . "Enjoy the site.\n\n"
	    . " -- The ".$GLOBALS['sys_name']." Team\n";

	mail($GLOBALS['form_email'], $GLOBALS['sys_name']." Account Registration",$message,"From: noreply@".$GLOBALS['sys_default_domain']);

	return 1;
    }
}

// ###### first check for valid login, if so, congratulate

if ($Register && register_valid()) {

    $HTML->header(array('title'=>'Register Confirmation'));
?>

<p><b><?php print $GLOBALS['sys_name']; ?>: New Account Registration Confirmation</b>
<p>Congratulations. You have registered on <?php print $GLOBALS['sys_name']; ?>.
Your new username is: <b><?php print user_getname($newuserid); ?></b>

<p>You are now being sent a confirmation email to verify your email 
address. Visiting the link sent to you in this email will activate
your account.

<?php

} else { // not valid registration, or first time to page

    $HTML->header(array('title'=> $GLOBALS['sys_name'].': Register'));

    if (browser_is_windows() && browser_is_ie() && browser_get_version() < '5.1') {
    }
    if (browser_is_ie() && browser_is_mac()) {
	echo '<H2><span class="highlight">Internet Explorer on the Macintosh
		is not supported currently. Use Netscape 4.7 or higher</span></H2>';
    }


?>
    
<h2><?php print $GLOBALS['sys_name']; ?> New Account Registration 
<?php echo help_button('UserRegistration.html');?></h2>

<?php 
if ($register_error) {
    print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
} ?>

<form action="/account/register.php" method="post">
<p>Login Name <strong>(Lower case only!)</strong> *:<br>
<input type="text" name="form_loginname" value="<?php print($form_loginname); ?>">

<p>Password (min. 6 chars) *:<br>
<input type="password" name="form_pw" value="<?php print($form_pw); ?>">

<p>Password (repeat) *:<br>
<input type="password" name="form_pw2" value="<?php print($form_pw2); ?>">

<P>Full/Real Name *:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print($form_realname); ?>">

<P>Email Address *:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print($form_email); ?>"><BR>
<? util_get_content('account/register_email'); ?>
<P>Timezone:<BR>
<?php echo html_get_timezone_popup ('timezone','GMT'); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked>
Receive Email about Site Updates <I>(Very low traffic and includes
security notices. Highly Recommended.)</I>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
Receive additional community mailings. <I>(Low traffic.)</I>

<p>
Fields marked with * are mandatory.
</p>
<p><input type="submit" name="Register" value="Register">

</form>
<?php
 }

$HTML->footer(array());
?>
