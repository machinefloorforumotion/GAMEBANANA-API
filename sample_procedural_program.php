<?php

include("gamebanana_api.php");

?>

<pre>
	<? print_r(api_request("Member",1382,"name","id")) ?>
</pre>

<pre>
	<? print_r(api_request("Member",1382,"name","Link().sGetProfileLink()")) ?>
</pre>

<pre>
	<? print_r(api_request("Member",1382,"name","Link().sGetProfileLink()","Url().sGetProfileUrl()","Url().sGetAvatarUrl()")) ?>
</pre>