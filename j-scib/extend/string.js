
String.extend({
	/* Property: stripTags
	 *
	 * Remove all html tags from a string
	 */
	stripTags: function(){
		return this.replace(/<\/?[^>]+>/gi, '');
	},

	/* Property: stripScripts
	 *
	 * Removes all script tags from an HTML string
	 */
	stripScripts: function(){
		return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
	},

	/* Property: evalScripts
	 *
	 * Executes scripts included in an HTML string
	 */
	evalScripts: function(){
		if( scripts=this.match(/<script[^>]*?>.*?<\/script>/g) ){
			scripts.each(function(script){
				eval(script.replace(/^<script[^>]*?>/, '').replace(/<\/script>$/, ''));
			});
		}
	},

	/* Property: replaceAll
	 *
	 * Replaces all instances of a string with the specified value.
	 *
	 * Arguments:
	 *  searchValue - the string you want to replace
	 *  replaceValue - the string you want to insert in the searchValue's place
	 *
	 * Example:
	 *  >"I like cheese".replaceAll("cheese", "cookies");
	 *  > > I like cookies
	 */
	replaceAll: function(searchValue, replaceValue) {
		var replaceRegex = new RegExp(searchValue,"g");
		var parsed = this.replace(replaceRegex, replaceValue);
		return parsed;
	},

	/* Property: urlEncode
	 * urlEncodes a string (if it is not already).
	 *
	 * Example:
	 * "Mondays aren't that fun".urlEncode(); -> "Mondays%20aren%27t%20that%20fun"
	 */
	urlEncode: function() {
		if (this.indexOf('%') > -1) return this;
		else return escape(this);
	},

	/* Property: parseQuery
	 *
	 * Turns a query string into an associative array of key/value pairs.
	 *
	 * Example:
	 * var query_object = "this=that&what=something".parseQuery(); ( returns > {this: "that", what: "something"} )
	 * query_object.this -> "that"
	 */
	parseQuery: function(){
		var pairs = this.match(/^\??(.*)$/)[1].split('&');
		var params = {};
		pairs.each(function(pair) {
			pair = pair.split('=');
			params[pair[0]] = pair[1];
		});
		return params;
	},

	/* Property: rTrim
	 *
	 * Remove whitespaces from right side
	 */
    rTrim: function(){
        elm         = this;
        rx_spaces   = /^([\w\W]*)(\b\s*)$/;
        return (rx_spaces.test(elm))?elm.replace(rx_spaces,'$1'):elm;
    },

	/* Property: lTrim
	 *
	 * Remove whitespaces from left side
	 */
    lTrim: function(){
        elm         = this;
        rx_spaces   = /^(\s*)(\b[\w\W]*)$/;
        return (rx_spaces.test(elm))?elm.replace(rx_spaces,'$2'):elm;
    },

	/* Property: trim
	 *
	 * Remove whitespaces from left and right side
	 */
    trim: function(){
        elm                         = this;
        rx_spaces                   = /^(\s*)$/;
        rx_leadingTrailingSlashes   = /^(\s*)([\W\w]*)(\b\s*$)/;
        if( rx_spaces.test(elm) ){
            elm = elm.replace(rx_spaces, '');
            if( elm.length==0 ) return elm;
        }
        if( rx_leadingTrailingSlashes.test(elm) )
            elm = elm.replace(rx_leadingTrailingSlashes, '$2');
        return elm;
    },

	/* Property: isEmpty
	 *
	 * Empty-String-Check
	 */
    isEmpty: function(){

        return ((this.trim()).length>0)?false:true;
    },

	/* Property: htmlEntities
	 *
	 * htmlEntities - 'äöü' > '&auml;&ouml;&uuml;'
	 */
    htmlEntities: function(){
        var elm         = this;
        var chars       = new Array ('&','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','€','\"','ß','<','>','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','­','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾');
        var entities    = new Array ('amp','agrave','aacute','acirc','atilde','auml','aring','aelig','ccedil','egrave','eacute','ecirc','euml','igrave','iacute','icirc','iuml','eth','ntilde','ograve','oacute','ocirc','otilde','ouml','oslash','ugrave','uacute','ucirc','uuml','yacute','thorn','yuml','Agrave','Aacute','Acirc','Atilde','Auml','Aring','AElig','Ccedil','Egrave','Eacute','Ecirc','Euml','Igrave','Iacute','Icirc','Iuml','ETH','Ntilde','Ograve','Oacute','Ocirc','Otilde','Ouml','Oslash','Ugrave','Uacute','Ucirc','Uuml','Yacute','THORN','euro','quot','szlig','lt','gt','cent','pound','curren','yen','brvbar','sect','uml','copy','ordf','laquo','not','shy','reg','macr','deg','plusmn','sup2','sup3','acute','micro','para','middot','cedil','sup1','ordm','raquo','frac14','frac12','frac34');
        for( var i=0; i<chars.length; i++ ){
            myRegExp = new RegExp();
            myRegExp.compile(chars[i],'g')
            elm = elm.replace (myRegExp, '&'+entities[i]+';');
        }
        return elm;
    },

	/* Property: numericEntities
	 */
    numericEntities: function(){
        var i, elm=this;
        var chars       = new Array ('&','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','€','\"','ß','<','>','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','­','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾');
        var entities    = new Array()
        for( i=0; i<chars.length; i++ ) entities[i] = chars[i].charCodeAt(0);

        for( i=0; i<chars.length; i++ ){
            myRegExp = new RegExp();
            myRegExp.compile(chars[i],'g')
            elm = elm.replace (myRegExp, '&#' + entities[i] + ';');
        }

        return elm;
    },
	removeSpecialChars: function(){
		var elm = this;
	    //var chars       = new Array ('&','à','á','â','ã','ä', 'å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ','À','Á','Â','Ã','Ä', 'Å','Æ', 'Ç','È','É','Ê','Ë', 'Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö', 'Ø','Ù','Ú','Û','Ü', 'Ý','Þ','€','\"','ß', '<','>','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','­','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾','\.',',',':',';','-','_','\+','\*','\~','\#','\'','\\','\?','\^','°','\"','§','\$','%','/','\(','\)','{','}','\[','\]','\=');
	    //var entities    = new Array ('', 'a','a','a','a','ae','a','', '', 'e','e','e','e','', '', '', '', '', 'n','o','o','o','o','o','', 'u','u','u','u','', '', 'y','A','A','A','A','Ae','', '',  '', 'E','E','E','E', 'I','I','I','I','', '', 'O','O','O','O','Oe','', 'U','U','U','Ue','', '', '','',  'ss','', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',  '',  '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
	    var chars       = new Array ('&','à','á','â','ã','ä', 'å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ','À','Á','Â','Ã','Ä', 'Å','Æ', 'Ç','È','É','Ê','Ë', 'Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö', 'Ø','Ù','Ú','Û','Ü', 'Ý','Þ','€','\"','ß', '<','>','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','­','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾');
	    var entities    = new Array ('', 'a','a','a','a','ae','a','', '', 'e','e','e','e','', '', '', '', '', 'n','o','o','o','o','o','', 'u','u','u','u','', '', 'y','A','A','A','A','Ae','', '',  '', 'E','E','E','E', 'I','I','I','I','', '', 'O','O','O','O','Oe','', 'U','U','U','Ue','', '', '','',  'ss','', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

		for( var i=0; i<chars.length; i++ ){
			myRegExp = new RegExp();
			myRegExp.compile(chars[i],'g')
			elm = elm.replace (myRegExp, entities[i]);
		}

		return elm;
	}
});
