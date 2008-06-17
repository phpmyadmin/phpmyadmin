<?php


?>
	<embed type="application/fbauth-plugin" width=1 height=1 hidden="true" id="fbauth"><br>
	<script>
	var glob_SwekeyPlugin = document.embeds["fbauth"];
	var glob_ValidSwekeyId;	
	var glob_ValidSwekeyOtp;	
	
	// -------------------------------------------------------------------
	// List the id of the Swekey connected to the PC 
	// Returns a string containing comma separated Swekey Ids
    // A Swekey id is a 32 char hexadecimal value.  	
	function Swekey_ListKeyIds()
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.SwekeyCtl");
				return x.list();
			}
			else
				return glob_SwekeyPlugin.list();
		}
		catch (e)
		{
//			alert("Swekey_ListKeyIds" + e);
		}
		return "";
	}

	// -------------------------------------------------------------------
	// Ask the Connected Swekey to generate an OTP
	// fbid: The id of the connected Swekey (returne by Swekey_ListKeyIds())
	// rt: A random token 	
	// return: The calculated OTP encoded in a 64 chars hexadecimal value.
	function Swekey_GetOtp(fbid, rt)
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.SwekeyCtl");
				return x.getotp(fbid, rt);
			}
			else
				return glob_SwekeyPlugin.getotp(fbid, rt);
		}
		catch (e)
		{
//			alert("Swekey_GetOtp " + e);
		}
		return "";
	}

	// -------------------------------------------------------------------
	// Set a unplug handler (url) to the specified connected swekey
	// fbid: The id of the connected Swekey (returne by Swekey_ListKeyIds())
	// key: The key that index that url, (aplhanumeric values only) 	
	// url: The url that will be launched ("" deletes the url)
	function Swekey_SetUnplugUrl(fbid, key, url)
	{
		try
		{
			if (window.ActiveXObject)
			{
				var x = new ActiveXObject("FbAuthAx.SwekeyCtl");
				return x.setunplugurl(fbid, key, url);
			}
			else
				return glob_SwekeyPlugin.setunplugurl(fbid, key, url);
		}
		catch (e)
		{
//			alert("Swekey_SetUnplugUrl " + e);
		}
	}

	// -------------------------------------------------------------------
	// Return a valid connected key id
	function Swekey_GetValidKey()
	{
	    var valids = <?php echo '"'.$_SESSION['PHP_AUTH_VALID_SWEKEYS'].'"';?>;
    	var connected_keys = Swekey_ListKeyIds().split(",");
     	for (i in connected_keys) 
       	    if (connected_keys[i] != null && connected_keys[i].length == 32)
        	    if (valids.indexOf(connected_keys[i]) >= 0)
        	       return connected_keys[i];

		return "none";
	}
	
	// -------------------------------------------------------------------
	// Return a valid connected key id
	function Swekey_GetOtpFromValidKey()
	{
        var key = Swekey_GetValidKey();
        if (key.length != 32)
            return "";

        var url = "" + window.location;

        if (url.indexOf("?") > 0)
            url = url.substr(0, url.indexOf("?"));

        if (url.lastIndexOf("/") > 0)
            url = url.substr(0, url.lastIndexOf("/"));
            
        Swekey_SetUnplugUrl(key, "pma_login", url + "/libraries/auth/swekey/unplugged.php?session_to_unset=<?php echo session_id();?>");
                
        return Swekey_GetOtp(key, <?php echo '"'.$_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN'].'"';?>);
    }
	</script>	
