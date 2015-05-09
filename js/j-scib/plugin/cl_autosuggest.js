

var CL_AutoSuggest = new Class({
	setOptions: function(options){
		this.sInput 		      = "",
		this.nInputChars 	      = 0,
		this.aSuggestions 	      = [],
		this.iHighlighted 	      = 0,
		this.id_result_container = 'cl_as_cont',
		this.id_result_list      = 'cl_as_list',

		this.options = {
			cache           : true,
			classname       : 'cl_as',
			delay           : 500,
			method          : 'post',
			minchars        : 1,
			noresults_show  : true,
			noresults_string: 'Keine Ergebnisse!',
			offsets         : {'x': 0,
			                   'y': 0,
			                   'w': -2,
			                   'h': 0},
			json            : false,
			timeout         : 2500
		};
		Object.extend(this.options, options || {});
	},
	initialize: function(fldId, options){
		this.setOptions(options);
		this.fld = $(fldId);

		var pointer = this;
		this.fld.onkeypress = function(ev) { return pointer.onKeyPress(ev); };
		this.fld.onkeyup    = function(ev) { return pointer.onKeyUp(ev); };
		this.fld.setAttribute("autocomplete","off");
	},

	onKeyPress: function(ev) {
		var key = (window.event) ? window.event.keyCode : ev.keyCode;
		// set responses to keydown events in the field this allows the user to use the arrow keys to scroll through the results
		// ESCAPE clears the list TAB sets the current highlighted value
		var RETURN = 13;
		var TAB    = 9;
		var ESC    = 27;
		var flag = true;

		switch (key) {
			case RETURN:
				this.setHighlightedValue();
				return false;
				break;

			case ESC:
				this.clearSuggestions();
				return true;
				break;
		}
	},

	onKeyUp: function(ev) {
		var key = (window.event) ? window.event.keyCode : ev.keyCode;
		// set responses to keydown events in the field this allows the user to use the arrow keys to scroll through the results
		// ESCAPE clears the list TAB sets the current highlighted value
		var ARRUP = 38;
		var ARRDN = 40;
		var bubble = true;

		switch (key) {
			case ARRUP:
				this.changeHighlight(key);
				bubble = false;
				break;

			case ARRDN:
				this.changeHighlight(key);
				bubble = false;
				break;

			default:
				this.getSuggestions(this.fld.value);
		}

		return bubble;
	},

	getSuggestions: function(val) {
		// if input stays the same, do nothing
		if (val==this.sInput)
			return false;

		// input length is less than the min required to trigger a request, reset input string, do nothing
		if (val.length<this.options.minchars) {
			this.sInput = '';
			return false;
		}

		// if caching enabled, and user is typing (ie. length of input is increasing), filter results out of aSuggestions from last request
		if (val.length>this.nInputChars && this.aSuggestions.length && this.options.cache) {
			var arr = [];
			for (var i=0; i<this.aSuggestions.length; i++) {
				if (this.aSuggestions[i].value.substr(0,val.length).toLowerCase()==val.toLowerCase())
					arr.push(this.aSuggestions[i]);
			}

			this.sInput       = val;
			this.nInputChars  = val.length;
			this.aSuggestions = arr;

			this.createList(this.aSuggestions);

			return false;
		} else	{ // do new request
			this.sInput      = val;
			this.nInputChars = val.length;

			var pointer = this;
			clearTimeout(this.ajID);
			this.ajID = setTimeout(function() { pointer.doAjaxRequest() }, this.options.delay);
		}

		return false;
	},

	doAjaxRequest: function() {
		var pointer      = this;
		var ajax_options = {};
		var url          = pointer.options.url;
		var method       = pointer.options.method.toLowerCase();
		var params       = { autosuggest: escape(pointer.fld.value) };
		if (pointer.options.json) params.json = true;

		switch (pointer.method) {
			case 'get':
				url+= '?'+Object.toQueryString(params);
				ajax_options.method = 'get';
				break;

			default:
				ajax_options.method   = 'post';
				ajax_options.postBody = Object.toQueryString(params);
				break;
		}

		ajax_options.onFailure  = function(e) {
			alert("AJAX error: "+e);
		};
		ajax_options.onComplete = function(responseText,responseXML) {
			pointer.setSuggestions(responseText,responseXML);
		};

		var autoSuggestAjax = new Ajax(url, ajax_options).request();
	},

	setSuggestions: function(requestText,requestXML) {
		this.aSuggestions = [];

		if (this.options.json==true) {
			var jsondata = eval('('+requestText+')');

			for (var i=0; i<jsondata.results.length; i++) {
				this.aSuggestions.push({
					'id'    : jsondata.results[i].id,
					'value' : jsondata.results[i].value,
					'info'  : jsondata.results[i].info
				});
			}
		} else {
			var xml     = requestXML;
			var results = xml.getElementsByTagName('results')[0].childNodes;

			for (var i=0; i<results.length; i++) {
				if (results[i].hasChildNodes())

				this.aSuggestions.push({
					'id'   : results[i].getAttribute('id'),
					'value': results[i].childNodes[0].nodeValue,
					'info' : results[i].getAttribute('info')
				});
			}
		}

		this.id_result_container+= '_'+this.fld.id;
		this.id_result_list+= '_'+this.fld.id;
		this.createList(this.aSuggestions);
	},

	createList: function(arr) {
		var pointer = this;

		if ($(this.id_result_container)) $(this.id_result_container).remove();
		this.killTimeout();

		// create holding div
		var div       = new Element('div');
		var div_inner = new Element('div');
		var ul        = new Element('ul');

		div.setProperty('id', pointer.id_result_container);
		div.addClass(pointer.options.classname);

		div_inner.addClass(pointer.options.classname+'_inner_div');

		ul.setProperty('id', pointer.id_result_list);


		if (arr.length>0) {
			// loop throught arr of suggestions creating an LI element for each suggestion
			for (var i=0; i<arr.length; i++) {
				var li = new Element('li');

				// format output with the input enclosed in a EM element (as HTML, not DOM)
				var val = arr[i].value;
				var st  = val.toLowerCase().indexOf(this.sInput.toLowerCase());
				var a   = new Element('a').setProperties({
					href: "javascript:;",
					name: i+1
				});

				new Element('em').appendText(val.substring(st, st+this.sInput.length)).injectInside(a);
				a.appendText(val.substring(st+this.sInput.length));

				if (arr[i].info!='')
					new Element('small').setHTML('<br>'+arr[i].info).injectInside(a);

				a.addEvent('click', function() {
					pointer.setHighlightedValue();
					return false;
				}).addEvent('mouseover', function() {
					pointer.setHighlight(this.name);
				});

				a.injectInside(li.injectInside(ul));
			}
		} else {
			var li = new Element('li');
			li.addClass('as_warning');
			li.appendText(pointer.options.noresults_string);
			li.injectInside(ul);
		}


		// Ausgabe der generierten Liste
		if ((arr.length>0) || (pointer.options.noresults_show==true)) {
			ul.injectInside(div_inner);
			div_inner.injectInside(div);
			var size     = $(pointer.fld).getSize();
			var position = $(pointer.fld).getPosition();

			div.setStyles({
				left  : (position.x.toInt()+pointer.options.offsets.x)+'px',
				top   : (position.y.toInt()+size.size.y.toInt()+pointer.options.offsets.y)+'px',
				width : (size.size.x.toInt()+pointer.options.offsets.w)+'px'
			});

			//if( pointer.options.maxheight.toInt>0 )
				//div.setStyle('height',pointer.options.maxheight.toInt+'px');

			div.addEvent('mouseover', function(){
				pointer.killTimeout()
			}).addEvent('mouseout', function(){
				pointer.resetTimeout()
			});

			div.injectInside(document.body);

			// currently no item is highlighted
			this.iHighlighted = 0;

			// remove list after an interval
			this.toID = setTimeout(function() { pointer.clearSuggestions() }, pointer.options.timeout);
		}
	},

	changeHighlight: function(key) {
		if (!$(this.id_result_list))
			return false;

		var list = $(this.id_result_list);
		var n    = '';

		if (key==40)
			n = this.iHighlighted + 1;
		else if (key==38)
			n = this.iHighlighted - 1;

		if (n>list.childNodes.length)
			n = list.childNodes.length;

		if (n<1)
			n = 1;

		this.setHighlight(n);
	},

	setHighlight: function(n) {
		if (!$(this.id_result_list))
			return false;

		var list = $(this.id_result_list);

		if (this.iHighlighted>0)
			this.clearHighlight();

		this.iHighlighted = Number(n);

		list.childNodes[this.iHighlighted-1].addClass("cl_as_highlight");

		this.killTimeout();
	},

	clearHighlight: function() {
		if (!$(this.id_result_list))
			return false;

		var list = $(this.id_result_list);

		if (this.iHighlighted>0) {
			list.childNodes[this.iHighlighted-1].removeClass("cl_as_highlight");
			this.iHighlighted = 0;
		}
	},

	setHighlightedValue: function() {
		if (this.iHighlighted) {
			this.sInput = this.fld.value = this.aSuggestions[this.iHighlighted-1].value;

			// move cursor to end of input (safari)
			this.fld.focus();

			if (this.fld.selectionStart)
				this.fld.setSelectionRange(this.sInput.length, this.sInput.length);

			this.clearSuggestions();

			// pass selected object to callback function, if exists
			if (typeof(this.options.callback)=="function")
				this.options.callback(this.aSuggestions[this.iHighlighted-1]);
		}
	},

	killTimeout: function() {
		clearTimeout(this.toID);
	},

	resetTimeout: function() {
		clearTimeout(this.toID);
		var pointer = this;
		this.toID   = setTimeout(function() { pointer.clearSuggestions() }, 1000);
	},

	clearSuggestions: function() {
		var pointer = this;
		pointer.killTimeout();

		if ($(pointer.id_result_container))
			$(pointer.id_result_container).remove();
	}
});

CL_AutoSuggest.implement(new Chain);