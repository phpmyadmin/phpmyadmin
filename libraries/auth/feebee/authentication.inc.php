<?php

    require_once "libraries/auth/feebee/fbauth.php";

    $_SESSION['PHP_AUTH_FEEBEE_RND_TOKEN'] = FbAuth_GetFastRndToken();

?>
	<embed type="application/fbauth-plugin" width=1 height=1 hidden="true" id="fbauth"><br>
	<script>
	var glob_FbAuthPlugin = document.embeds["fbauth"];
	var glob_ValidFeebeeId;	
	var glob_ValidFeebeeOtp;	
	
	// -------------------------------------------------------------------
	// List the id of the Feebee connected to the PC 
	// Returns a string containing comma separated Feebee Ids
    // A Feebee id is a 32 char hexadecimal value.  	
	function FbAuth_ListKeyIds()
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.FbAuthCtl");
				return x.list();
			}
			else
				return glob_FbAuthPlugin.list();
		}
		catch (e)
		{
//			alert("FbAuth_ListKeyIds" + e);
		}
		return "";
	}

	// -------------------------------------------------------------------
	// Ask the Connected Feebee to generate an OTP
	// fbid: The id of the connected Feebee (returne by FbAuth_ListKeyIds())
	// rt: A random token 	
	// return: The calculated OTP encoded in a 64 chars hexadecimal value.
	function FbAuth_GetOtp(fbid, rt)
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.FbAuthCtl");
				return x.getotp(fbid, rt);
			}
			else
				return glob_FbAuthPlugin.getotp(fbid, rt);
		}
		catch (e)
		{
//			alert("FbAuth_GetOtp " + e);
		}
		return "";
	}

	// -------------------------------------------------------------------
	// Set a unplug handler (url) to the specified connected feebee
	// fbid: The id of the connected Feebee (returne by FbAuth_ListKeyIds())
	// key: The key that index that url, (aplhanumeric values only) 	
	// url: The url that will be launched ("" deletes the url)
	function FbAuth_SetUnplugUrl(fbid, key, url)
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.FbAuthCtl");
				return x.setunplugurl(fbid, key, url);
			}
			else
				return glob_FbAuthPlugin.setunplugurl(fbid, key, url);
		}
		catch (e)
		{
//			alert("FbAuth_SetUnplugUrl " + e);
		}
	}

	// -------------------------------------------------------------------
	// Return a valid connected key id
	function Feebee_GetValidKey()
	{
	    var valids = <?php echo '"'.$_SESSION['PHP_AUTH_VALID_FEEBEES'].'"';?>;
    	var connected_keys = FbAuth_ListKeyIds().split(",");
     	for (i in connected_keys) 
       	    if (connected_keys[i] != null && connected_keys[i].length == 32)
        	    if (valids.indexOf(connected_keys[i]) >= 0)
        	       return connected_keys[i];

		return "none";
	}
	
	// -------------------------------------------------------------------
	// Return a valid connected key id
	function Feebee_GetOtp()
	{
        var key = Feebee_GetValidKey();
        if (key.length != 32)
            return "";

        var url = "" + window.location;

        if (url.indexOf("?") > 0)
            url = url.substr(0, url.indexOf("?"));

        if (url.lastIndexOf("/") > 0)
            url = url.substr(0, url.lastIndexOf("/"));
            
        FbAuth_SetUnplugUrl(key, "pma_login", url + "/libraries/auth/feebee/unplugged.php?session_to_unset=<?php echo session_id();?>");
                
        return FbAuth_GetOtp(key, <?php echo '"'.$_SESSION['PHP_AUTH_FEEBEE_RND_TOKEN'].'"';?>);
    }
	</script>	
	
<?php

?>
