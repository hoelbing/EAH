/*	Script: function.cnet.js
		Extends functionality in Mootools <Function.js>
		
		Dependencies:
		<Moo.js>, <Function.js>
		
		Author:
		Aaron Newton - aaron [dot] newton [at] cnet [dot] com

		Function: $type
		Extends the <$type> function in <Function.js>

		Property: isNumber
		Determines if a value is a number. If the value is a string, if the string will parse
		to a number, returns true.
		
		Arguments:
		val - the object to asses.
		
		Example:
		>$type.isNumber(myValue) //if it's a number, returns true
	*/
		$type.isNumber = function(val) {
			if((typeof val != "undefined" && typeof val == "number") ||
			(typeof val != "boolean" && (typeof val != "string" || val.length >0) && isFinite(new Number(val)))) return true;
			return false;
		};

/*	Property.isSet
		Determines if a value is defined.
		
		Arguments:
		val - the object to asses
		
		Example:
		>$type.isSet(myValue) //if it's not undefined or null, returns true
	*/		
		$type.isSet = function(val){
			return (typeof val != "undefined" && val != null);
		};
/*	Function:	$set
		returns a value if it's defined, otherwise sets it to a default and returns it.
		
		Arguments:
		val - the value to test
		defaultVal - the default value to set val to, if it's undefined
		
		Examples:
		(start code)
if($set(myVal, false)) .... 
	// if myVal is defined, evaluate it and then execute my code or 
	//don't, otherwise set it to false and evaluate that.
		(end)
*/
		function $set(val, defaultVal){
			if(typeof val == "undefined" || val == null) val = defaultVal;
			return val;
		};
		
/* do not edit below this line */   
/* Section: Change Log 

$Source: /usr/local/cvsroot/uni_postbuch/j-scib/extend/function.js,v $
$Log: function.js,v $
Revision 1.1  2007/02/21 14:46:03  heiko
*** empty log message ***

Revision 1.1  2007/01/15 12:29:10  heiko
*** empty log message ***

Revision 1.1  2006/12/15 14:37:35  heiko
*** empty log message ***

Revision 1.4  2006/11/15 01:18:45  newtona
updated docs

Revision 1.3  2006/11/13 22:56:01  newtona
added function $set

Revision 1.2  2006/11/02 21:34:00  newtona
Added cvs footer


*/
