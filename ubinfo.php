<?PHP

# ubinfo.php: this file shows the variables available for the
# logged-in UserBase user, and the require() line that's used
# to load those variables.

require($_SERVER['DOCUMENT_ROOT'] . "/login/ubvars.php");

print "<br />\n Username:	$ub_username";
print "<br />\n UserID:		$ub_userid";
print "<br />\n Member?:	$ub_is_member";
print "<br />\n Admin?:		$ub_is_admin";
print "<br />\n RealName:	$ub_realname";
print "<br />\n Email:		$ub_email";
print "<br />\n GroupMemberships: " . implode(",", $ub_group_list) . "<br />\n";

if($ub_vars)
{
	foreach($ub_vars as $var => $value)
	{
		echo "<br />\n Custom var $var: $value";
	}
}

?>
