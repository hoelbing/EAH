/*
Script: fixpng.js

Dependancies:
	 mootools - <Moo.js>, <String.js>, <Array.js>, <Function.js>, <Element.js>, <Dom.js>
	
Author:
	Aaron Newton, <aaron [dot] newton [at] cnet [dot] com>

		Function: fixPNG
		this will make transparent pngs show up correctly in IE. This function 
		is based almost entirely on the function found here: 
		<http://homepage.ntlworld.com/bobosola/pnginfo.htm>
		
		Arguments:
		myImage - the image element or id to fix
		
		Note: 
		there is an instances of this already set to fire onDOMReady that
		will fix any png files with the class "fixPNG". This means any producer
		can just give the class "fixPNG" to any img tag and they are set BUT, the
		ping will look wrong until the DOM loads, which may or may not be noticeable.
		
		The alternative is to embed the call right after the image like so:
		
		><img src="png1.png" width="50" height="50" id="png1">
		><img src="png2.png" width="50" height="50" id="png2">
		><script>
		>	$$('#png1', '#png2').each(function(png) {fixPNG(png);});
		>	//OR
		>	fixPNG('png1');
		>	fixPNG('png2');
		></script>
*/

function fixPNG(myImage) 
{
	try {
		var arVersion = navigator.appVersion.split("MSIE");
		var version = parseFloat(arVersion[1]);
		if ((version >= 5.5) && (version < 7) && (document.body.filters)){
			myImage = $(myImage);
			var vis = myImage.visible();
			if(!vis) myImage.show();
			var width = $(myImage).offsetWidth;
			var height = $(myImage).offsetHeight;
			if(!vis) myImage.hide();
			var imgID = (myImage.id) ? "id='" + myImage.id + "' " : "";
			var imgClass = (myImage.className) ? "class='" + myImage.className + "' " : "";
			var imgTitle = (myImage.title) ? "title='" + myImage.title	+ "' " : "title='" + myImage.alt + "' ";
			var imgStyle = "display:inline-block;" + myImage.style.cssText;
			var strNewHTML = "<span " + imgID + imgClass + imgTitle
									+ " style=\"" + "width:" + width 
									+ "px; height:" + height 
									+ "px;" + imgStyle + ";"
									+ "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
									+ "(src=\'" + myImage.src + "\', sizingMethod='scale');\"></span>";
			myImage.outerHTML = strNewHTML;
		}
	} catch(e) {}
};
if(window.ie6) window.onDomReady(function(){$$('img.fixPNG').each(function(png){fixPNG(png)});});
/* do not edit below this line */   
/* Section: Change Log 

$Source: /usr/local/cvsroot/uni_postbuch/j-scib/widget/fixpng.js,v $
$Log: fixpng.js,v $
Revision 1.1  2007/02/21 14:46:03  heiko
*** empty log message ***

Revision 1.3  2007/01/26 05:46:32  newtona
syntax update for mootools 1.0

Revision 1.2  2007/01/19 01:21:47  newtona
changed event.ondomready > window.ondomready

Revision 1.1  2007/01/09 02:39:35  newtona
renamed addons directory to "common" directory

Revision 1.3  2007/01/09 01:26:38  newtona
changed $S to $$

Revision 1.2  2006/11/02 21:26:42  newtona
checking in commerce release version of global framework.

notable changes here:
cnet.functions.js is the only file really modified, the rest are just getting cvs footers (again).

cnet.functions adds numerous new classes:

$type.isNumber
$type.isSet
$set

*/