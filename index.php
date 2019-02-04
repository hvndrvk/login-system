<?PHP

#include("http://" . $_SERVER['HTTP_HOST'] . "/cgi-bin/userbase.cgi?ubsessioncode=" . $_COOKIE['site_session'] . '&' . $_SERVER['QUERY_STRING']);

if(function_exists('virtual'))
{
	virtual("/cgi-bin/userbase.cgi?" . $_SERVER['QUERY_STRING']);
}
else
{
	require("call_ub.php");
}

?>
