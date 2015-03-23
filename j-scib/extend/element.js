/*	Script: element.cnet.js
Extends the <Element> object.

Dependancies:
	 mootools - <Moo.js>, <String.js>, <Array.js>, <Function.js>, <Element.js>, <Dom.js>

Author:
	Aaron Newton, <aaron [dot] newton [at] cnet [dot] com>
	
Class: Element
		This extends the <Element> prototype.
	*/
Element.extend({
/*	Property: getDimensions
		Returns the width and height of an element.
		
		Example:
		>$(id).getDimensions()
		> > {width: #, height: #}
	*/
	getDimensions: function() {
		return {width: this.getStyle('width', true), height: this.getStyle('height', true)};
	},	
/*	Property: visible
		Returns a boolean; true = visible, false = not visible.
		
		Example:
		>$(id).visible()
		> > true | false	*/
	visible: function() {
		return this.getStyle('display') != 'none';
	},
/*	Property: toggle
		Toggles the state of an element from hidden (display = none) to 
		visible (display = what it was previously or else display = block)
		
		Example:
		> $(id).toggle()
	*/
	toggle: function() {
		return this[this.visible() ? 'hide' : 'show']();
	},
/*	Property: hide
		Hides an element (display = none)
		
		Example:
		> $(id).hide()
		*/
	hide: function() {
		this.originalDisplay = this.style.display; 
		this.style.display = 'none';
		return this;
	},
/*	Property: show
		Shows an element (display = what it was previously or else display = block)
		
		Example:
		>$(id).show() */
	show: function(display) {
		this.style.display = display || this.originalDisplay || 'block';
		return this;
	},
/*	Property: cleanWhitespace
		Removes all empty text nodes from an element and its children
		
		Example:
		> $(id).cleanWhitespace()	*/
	cleanWhitespace: function() {
		$A(this.childNodes).each(function(node){
			if (node.nodeType == 3 && !/\S/.test(node.nodeValue)) node.parentNode.removeChild(node);
		});
	},
/*	Property: find
		Returns an element from the node's array (such as parentNode), deprecated (left over from Prototype.lite).
		
		Arguments:
		what - the value you wish to find (such as 'parentNode')

		Example:
		> $(id).find(parentNode)
	*/
	find: function(what) {
		var element = this[what];
		while (element.nodeType != 1) element = element[what];
		return element;
	},
/*	Property: replace
		Replaces an html element with the html passed in.
		
		Arguments:
		html - the html with which to replace the node.
		
		Example:
		>$(id).replace(myHTML) */
	replace: function(html) {
		if (this.outerHTML) {
			this.outerHTML = html.stripScripts();
		} else {
			var range = this.ownerDocument.createRange();
			range.selectNodeContents(this);
			this.parentNode.replaceChild(
				range.createContextualFragment(html.stripScripts()), this);
		}
		setTimeout(function() {html.evalScripts()}, 10);
	},
/*	Property: empty
		Returns a boolean: true = the Node is empty, false, it isn't.
		
		Example:
		> $(id).empty
		> true (the node is empty) | false (the node is not empty)
	*/
	empty: function() {
		return this.innerHTML.match(/^\s*$/);
	},
	/*	Property: getOffsetHeight
			Returns the offset height of an element, deprecated.
			You should instead use <Element.getStyle>('height').
			
			Example:
			> $(id).getOffsetHeight()
		*/
	getOffsetHeight: function(){ return this.getStyle('height'); },
	/*	Property: getOffsetWidth
			Returns the offset width of an element, deprecated.
			You should instead use <Element.getStyle>('width').
			
			Example:
			> $(id).getOffsetWidth()
		*/
	getOffsetWidth: function(){ return this.getStyle('width'); }
});
/* do not edit below this line */   
/* Section: Change Log 

$Source: /usr/local/cvsroot/uni_postbuch/j-scib/extend/element.js,v $
$Log: element.js,v $
Revision 1.1  2007/02/21 14:46:03  heiko
*** empty log message ***

Revision 1.1  2007/01/15 12:29:10  heiko
*** empty log message ***

Revision 1.1  2006/12/15 14:37:35  heiko
*** empty log message ***

Revision 1.2  2006/11/02 21:34:00  newtona
Added cvs footer


*/
