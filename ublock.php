<?PHP
	# ublock.php: this file/page is not visited directly; instead you
	# require() it from within another page which you want to protect.
	# See the ubtest.php file for an example.



	$DOCROOT = $DOCUMENT_ROOT ? $DOCUMENT_ROOT : $_SERVER['DOCUMENT_ROOT'];
	#
	# On Windows servers, you may need to set $DOCROOT manually.  Check the
	# PATH_TRANSLATED environment variable to see what the path should be.
	# You can run the  phpinfo()  function to see that variable's value.



	# Method 1:
	#ob_start();
	#$login_check = $DOCROOT . '/cgi-bin/userbase.cgi?action=chklogin';
	#virtual($login_check);
	#$login_status = ob_get_contents();
	#ob_end_clean();




	# Method 2:
	# Note that with this method, the IP received by the CGI script will be the server's
	# IP, not the end-user's IP, so any features that rely on that (for example UserBase's
	# "restrict this session to my IP" feature) must be disabled because they won't work.
	#
	#ob_start();
	#$login_check = "http://" . $_SERVER['HTTP_HOST'] . "/cgi-bin/userbase.cgi?action=chklogin&ubsessioncode=" . $_COOKIE['site_session'];
	#include($login_check);
	#$login_status = ob_get_contents();
	#ob_end_clean();




	# Method 3:
	# Change only these 2 lines, to match the path & name of your CGI script:
	$cgi_script_local	= "/cgi-bin/userbase.cgi";
	$cgi_script_full	= "$DOCROOT/cgi-bin/userbase.cgi";

	$cgi_script_full_alt	= "$DOCROOT/../cgi-bin/userbase.cgi";
	$cgi_script_full_altwin	= "$DOCROOT/login/userbase.cgi";

	if(!(file_exists($cgi_script_full)))
	{
		if(file_exists($cgi_script_full_alt))
		{
			$cgi_script_full = $cgi_script_full_alt;
		}
		elseif(file_exists($cgi_script_full_altwin))
		{
			$cgi_script_local = "/login/userbase.cgi";
			$cgi_script_full = $cgi_script_full_altwin;
		}
		else
		{
			print "Error: the file specified by \$cgi_script_full does not exist ('$cgi_script_full').  You may need to edit your ublock.php file and manually set the \$DOCROOT and/or \$cgi_script_full variables.";
			exit;
		}
	}

	reset($_SERVER);
	$qs_set = 0;
	while (list ($header, $value) = each ($_SERVER))
	{
		if($header == "SCRIPT_NAME" || $header == "SCRIPT_URL")
		{
			putenv("$header=$cgi_script_local");
		}
		elseif($header == "SCRIPT_FILENAME")
		{
			putenv("$header=$cgi_script_full");
		}
		elseif($header == "SCRIPT_URI")
		{
			$value = str_replace($_SERVER['SCRIPT_URL'], $cgi_script_local, $value);
			putenv("$header=$value");
		}
		elseif($header == "QUERY_STRING")
		{
			putenv("$header=action=chklogin&code=" . $_COOKIE['site_session']);
			$qs_set = 1;
		}
		elseif($header == "DOCUMENT_ROOT")
		{
			putenv("$header=$DOCROOT");
		}
		else
		{
			putenv("$header=$value");
		}
	}
	if(!$qs_set)
	{
		putenv("QUERY_STRING=action=chklogin&code=" . $_COOKIE['site_session']);
	}

	unset($output);
	unset($output_body);
	exec($cgi_script_full, $output, $return_val);
	if(!$output)
	{
		exec("perl $cgi_script_full", $output, $return_val);
	}

	$html_headers_finished = 0;
	foreach ($output as $line)
	{
		if($html_headers_finished)
		{
			$output_body .= "$line\n";
		}
		else
		{
			if($line == '')
			{
				$html_headers_finished = 1;
			}
		}
	}
	$login_status = $output_body;


	# Now unset these so as not to confuse any CGI scripts that we call after this one:
	reset($_SERVER);
	while (list ($header, $value) = each ($_SERVER))
	{
		$status = putenv($header) ? 'succeeded' : 'failed';
		#print "<!-- $status unsetting var $header -->\n";
	}



	$allowed = 0;
	$ub_admin = 0;
	$ub_member = 0;
	$ub_username = '';
	$ub_userid = '';

	if(!$groups_allowed && !$users_allowed)
	{
		$groups_allowed = 'member';
	}

	$allowed_groups_list = explode(",", $groups_allowed); # set $groups_allowed in the file that include()s this one.
	$allowed_groups_hash = array();
	foreach($allowed_groups_list as $group)
	{
		if($group)
		{
			$allowed_groups_hash[$group] = 1;
		}
	}

	$allowed_users_list = explode(",", $users_allowed); # set $users_allowed in the file that include()s this one.
	$allowed_users_hash = array();
	foreach($allowed_users_list as $username)
	{
		if($username)
		{
			$allowed_users_hash[$username] = 1;
		}
	}

	if(preg_match("/^admin=(0|1):::::member=(0|1):::::username=(.*?):::::userid=(\d*?):::::group_memberships=(.*?):::::/", $login_status, $matches))
	{
		$ub_admin = $matches[1];
		$ub_member = $matches[2];
		$ub_username = $matches[3];
		$ub_userid = $matches[4];
		$group_memberships = $matches[5];

		$group_list = explode(",", $group_memberships);
		foreach($group_list as $group)
		{
			if($allowed_groups_hash[$group])
			{
				$allowed = 1;
			}
		}

		if($allowed_users_hash[$ub_username])
		{
			$allowed = 1;
		}
	}

	$ub_url = "http://" . $_SERVER['HTTP_HOST'] . '/login/';

	#print "\n<br />groups_allowed: '$groups_allowed' \n<br />login_check: '$login_check' \n<br />login_status: '$login_status' \n<br />\n";
	if(!$allowed   &&   !$ub_member)
	{
		#print "<h1>Authentication Required</h1>\n<p>You must <a href=\"/login/\">login</a> first.</p>\n";

		# Or, if on a crappy server where you must use include() to call userbase.cgi:
		#
		#print "<h1>Authentication Required</h1>\n<p>You must <a href=\"/login/?whence=" . $_SERVER['REQUEST_URI'] . "\">login</a> first.</p>\n";

		# Or, if you want to auto-redirect to the login page, instead of
		# displaying the "you must log in" message:
		#
		$gourl = $ub_url . "?phasemsg=elfirst&whence=" . $_SERVER['REQUEST_URI'];

		# Or if you want to use UserBase on a main domain to protect pages on subdomains, use this
		# instead (and set DOCROOT [at the top of this script] to your main domain's DOCUMENT_ROOT):
		#
		#$gourl = $ub_url . "?phasemsg=elfirst&whence=" . "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		print "<html><head><meta http-equiv=\"refresh\" content=\"0;url=$gourl\"></head><body></body></html>\n";
		exit;
	}

	if(!$allowed   &&   $ub_member)
	{
		$gourl = $ub_url . "?phase=edenied";
		print "<html><head><meta http-equiv=\"refresh\" content=\"0;url=$gourl\"></head><body></body></html>\n";
		exit;
	}
?>
