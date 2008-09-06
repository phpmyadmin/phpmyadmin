
<script>

	var g_SwekeyPlugin = null;

	// -------------------------------------------------------------------
	// Create the swekey plugin if it does not exists
	function Swekey_Plugin()
	{
		try 
		{				
			if (g_SwekeyPlugin != null)
				return g_SwekeyPlugin;
		
			if (window.ActiveXObject)
			{
				g_SwekeyPlugin = new ActiveXObject("FbAuthAx.FbAuthCtl")
				return g_SwekeyPlugin;
			}
	
			g_SwekeyPlugin = document.embeds["script_generated_swekey_plugin"];
			if (g_SwekeyPlugin != null)
				return g_SwekeyPlugin;
				
			for (x = 0; x < navigator.plugins.length; x ++)
			{
				try
				{
					if (navigator.plugins[x][0].type == "application/fbauth-plugin")
					{
						var x = document.createElement('embed');
						x.setAttribute('type', 'application/fbauth-plugin');
						x.setAttribute('id', 'script_generated_swekey_plugin');
						x.setAttribute('width', '0');
						x.setAttribute('height', '0');
						x.setAttribute('hidden', 'true');
						document.body.appendChild(x);
						g_SwekeyPlugin = document.embeds["script_generated_swekey_plugin"];
						return g_SwekeyPlugin;
					}
				}
				catch (e)
				{
				}
			}
		}
		catch (e) 
		{
//			alert("Swekey_Plugin " + e);
			g_SwekeyPlugin = null;
		}
		return null;
	}
	
	// -------------------------------------------------------------------
	// Returns true if the swekey plugin is installed
	function Swekey_Installed()
	{
		return (Swekey_Plugin() != null);
	}
	
	// -------------------------------------------------------------------
	// List the id of the Swekey connected to the PC 
	// Returns a string containing comma separated Swekey Ids
    // A Swekey is a 32 char hexadecimal value.  	
	function Swekey_ListKeyIds()
	{
		try
		{
			return Swekey_Plugin().list();
		}
		catch (e)
		{
//			alert("Swekey_ListKeyIds " + e);
		}
		return "";
	}
	
	// -------------------------------------------------------------------
	// Ask the Connected Swekey to generate an OTP
	// id: The id of the connected Swekey (returne by Swekey_ListKeyIds())
	// rt: A random token 	
	// return: The calculated OTP encoded in a 64 chars hexadecimal value.
	function Swekey_GetOtp(id, rt)
	{
		try
		{
			return Swekey_Plugin().getotp(id, rt);
		}
		catch (e)
		{
//			alert("Swekey_GetOtp " + e);
		}
		return "";
	}

	// -------------------------------------------------------------------
	// Set a unplug handler (url) to the specified connected feebee
	// id: The id of the connected Swekey (returne by Swekey_ListKeyIds())
	// key: The key that index that url, (aplhanumeric values only) 	
	// url: The url that will be launched ("" deletes the url)
	function Swekey_SetUnplugUrl(id, key, url)
	{
		try
		{
			return Swekey_Plugin().setunplugurl(id, key, url);
		}
		catch (e)
		{
//			alert("Swekey_SetUnplugUrl " + e);
		}
	}
</script>	
