/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
var http_request = false;
var xmldoc;
var textdoc;


function makeRequest(url, parameters)
{
  http_request = false;
  if (window.XMLHttpRequest)
  { // Mozilla, Safari,...
    http_request = new XMLHttpRequest();
    if (http_request.overrideMimeType) { http_request.overrideMimeType('text/xml'); }
  }
  else
  if (window.ActiveXObject)
  { // IE
    try { http_request = new ActiveXObject("Msxml2.XMLHTTP"); }
    catch (e)
    {
      try { http_request = new ActiveXObject("Microsoft.XMLHTTP"); }
      catch (e) {}
    }
  }

  if (!http_request)
  {
    alert('Giving up :( Cannot create an XMLHTTP instance');
    return false;
  }

  http_request.onreadystatechange = alertContents;
  http_request.open('POST', url, true);
  http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  http_request.setRequestHeader("Content-length", parameters.length);
  http_request.setRequestHeader("Connection", "close");
  http_request.send(parameters);
}

function alertContents()
{
  if (http_request.readyState == 1 )
  {
    document.getElementById("layer_action").style.left = document.body.clientWidth + document.body.scrollLeft - 85;
    document.getElementById("layer_action").style.top  = document.body.scrollTop + 10;
    document.getElementById("layer_action").style.visibility = 'visible'; document.getElementById("layer_action").innerHTML  = 'Loading...';
  }
  if (http_request.readyState == 2 ) document.getElementById("layer_action").innerHTML  = 'Loaded';
  if (http_request.readyState == 3 ) document.getElementById("layer_action").innerHTML  = 'Loading 99%';
  if (http_request.readyState == 4 )
  {
    if (http_request.status == 200)
    {
      textdoc = http_request.responseText;
      //alert(textdoc);
      xmldoc  = http_request.responseXML;
      PrintXML();
      document.getElementById("layer_action").style.visibility = 'hidden';
    }
    else
    {
      alert('There was a problem with the request.');
    }
  }
}

function PrintXML()
{
  var root = xmldoc.getElementsByTagName('root').item(0);  //root
  //alert(xmldoc.getElementsByTagName('root').item(1));
  if(root==null) // if error
  {
    myWin=window.open('','Report','width=400, height=250, resizable=1, scrollbars=1, status=1');
    var tmp = myWin.document;
    tmp.write(textdoc);
    tmp.close();
  }
  else
  {
    //alert(xmldoc.getElementsByTagName('root')[0]);
    //alert(root.attributes[0].nodeValue);
    //alert(xmldoc.getElementsByTagName('root')[0].attributes[0].nodeValue);
    //xmldoc.getElementsByTagName('root')[0].getAttribute("act")

    if(root.getAttribute('act') == 'save_pos')
      alert(strLang[root.getAttribute('return')]);
    if(root.getAttribute('act') == 'relation_upd')
    {
      alert(strLang[root.getAttribute('return')]);
      if(root.getAttribute('b')=='1')
      {
        contr.splice(root.getAttribute('K'), 1);
        Re_load();
      }
    }
    if(root.getAttribute('act') == 'relation_new')
    {
      alert(strLang[root.getAttribute('return')]);
      if(root.getAttribute('b')=='1')
      {
        var i  = contr.length;
        var t1 = root.getAttribute('DB1') + '.' + root.getAttribute('T1');
        var f1 = root.getAttribute('F1');
        var t2 = root.getAttribute('DB2') + '.' + root.getAttribute('T2');
        var f2 = root.getAttribute('F2');
        contr[i] = new Array();
        contr[i][''] = new Array();
         contr[i][''][t2] = new Array();
           contr[i][''][t2][f2] = new Array();
           contr[i][''][t2][f2][0] = t1;
           contr[i][''][t2][f2][1] = f1;
        Re_load();
      }
    }
  }
}




