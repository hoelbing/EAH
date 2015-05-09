/*
 * Script: load.js
 *
 * Author:
 *   Heiko Pfefferkorn (<http://ifabrik.de>)
 * License:
 *   MIT style license (<http://www.opensource.org/licenses/mit-license.php>, <http://de.wikipedia.org/wiki/MIT-Lizenz>)
 * Credits:
 *   - Funktionalität basiert auf mootools.v1.00.js (<http://mootools.net>), MIT style license <http://www.opensource.org/licenses/mit-license.php>, <http://de.wikipedia.org/wiki/MIT-Lizenz>
 *   - Dokumentation Heiko Pfefferkorn (<http://ifabrik.de>)
 * Copyright:
 *   @copyright 2007 Heiko Pfefferkorn
 */

if (!(Object.extend && Object.Native))
	throw("I.J.LIBRARY requires the object-oriented javascript framework 'mootools' >= release 1.00 (mootools.net)");

/*
 * Class: I_J_LIBRARY
 *   Klasse für das benutzerdefinierte Einbinden von vorhandenen Plugins bzw. Modulen der I-J-Library.
 *   Es muss zwingend vorher 'mootools' >= release 1.00 eingebunden sein (siehe Example)
 * Arguments:
 *   Benutzeranweisungen werden aus dem Querystring, sofern vorhanden, extrahiert
 *   - Query-Argument 'include' bewirkt das nur die angegebenen Plugins bzw. Module eingebunden werden.
 *   - Query-Argument 'exclude' bewirkt das alle Plugins bzw. Module eingebunden werden bis auf die angegebenen
 *   - Query-Argument 'lang' bewirkt das die Standardsprache (de) durch die angegebenen überschrieben wird (näheres im JS-Quellcode)
 *   Query-Argument 'include' hat Vorrang vor 'exclude'.
 * Example:
 *   (start code)
 *   <script language="javascript" type="text/javascript" src="i-j-library/mootools.v1.00.js"></script>
 *   <script language="javascript" type="text/javascript" src="i-j-library/load.js?lang=en&include=autosuggest"></script>
 *   (end)
 */
var I_J_LIBRARY = new Class({
	/*
	 * Property: initialize
	 *   Hinterlegung aller zu Verfügung stehenden Plugins bzw. Module in einer Objektstruktur
	 */
    initialize: function(){
        this.options = {
			module: {
				ext_string       : {p: 'extend/',          f: ['string.js']},
				ext_window       : {p: 'extend/',          f: ['window.js']},
				pl_validate_form : {p: 'plugin/',          f: ['validate_form.js']},
				pl_mooprompt     : {p: 'plugin/',          f: ['mooprompt.js']},
				pl_slimbox       : {p: 'plugin/',          f: ['slimbox.js']},
				pl_cl_autosuggest: {p: 'plugin/',          f: ['cl_autosuggest.js']},
				pl_calendar      : {p: 'plugin/calendar/', f: ['calendar.js','lang/calendar-{lang}.js','calendar-setup.js']},
				wdg_datecheck    : {p: 'widget/',          f: ['datecheck.js']},
				wdg_fixpng       : {p: 'widget/',          f: ['fixpng.js']}
			},
            defaultLang: 'de',
            path: ''
        };
        this.doc = document;
		this.load();
	},
	/*
	 * Property: load
	 *   Holt sich aus dem Seitenkopf den 'load.js'-Aufruf und sorgt für die korrekte Einbindung der gewünschten Plugins bzw. Module
	 */
	load: function(){
		var pointer = this;
		var to_load = {};

        if ($ES('script', $$('head'))) {
            $ES('script', $$('head')).each(function(s){
				var s_source = s.getProperty('src');

                if (s_source && s_source.match(/j-scib\/load\.js(\?.*)?$/)) {
                    pointer.options.path = s_source.replace(/load\.js(\?.*)?$/,'');

					// Hash des Querystring erzeugen
					var hash_query = new Hash(pointer.parseQuerystring(s_source));

					// Standardsprache überschreiben?
					if (hash_query.hasKey('lang'))
						pointer.options.defaultLang = hash_query.get('lang').toLowerCase();

					var hash_module = new Hash(pointer.options.module);

					// nur benutzerdefinierte Module einbinden
					if (hash_query.hasKey('include')) {
						hash_query.get('include').split(',').each(function(o) {
							if ($type(pointer.options.module[o])=='object') {
								var oPath = pointer.options.module[o].p;
								pointer.options.module[o].f.each(function(file) {
									pointer.include(pointer.options.path+oPath+file.replace(/\{lang\}/,pointer.options.defaultLang));
								});
							}
						});
					} else {
						hash_module = new Hash(pointer.options.module);

						// benutzerdefinierte Module auslassen
						if (hash_query.hasKey('exclude'))
							hash_query.get('exclude').split(',').each(function(o) {
								hash_module.remove(o);
							});

						// übriggebliebene Module einbinden
						hash_module.each(function(o) {
							if ($type(pointer.options.module[o])=='object') {
								var oPath = pointer.options.module[o].p;
								pointer.options.module[o].f.each(function(file) {
									pointer.include(pointer.options.path+oPath+file.replace(/\{lang\}/,pointer.options.defaultLang));
								});
							}
						});
					}
                }
            });
        }
	},
	/*
	 * Property: parseQuerystring
	 *   Gibt einen vorhandenen Querystring als Objekt zurück
	 * Arguments:
	 *   url - die Source-URL von dem eingebundenen 'load.js' (siehe oben Class - Exmaple)
	 */
	parseQuerystring: function(url) {
		var query = $pick(url);

		if (query=='')
			return null;

		if (query.indexOf("?")>=0)
			query = query.substring(query.indexOf("?")+1, query.length);

		var pairs = query.match(/^\??(.*)$/)[1].split('&');
		var params = {};
		pairs.each(function(pair) {
			pair = pair.split('=');
			params[pair[0]] = pair[1];
		});
		return params;
	},
	/*
	 * Property: include
	 *   Bindet das entsprechende Plugin bzw. Modul im Dokument ein
	 * Arguments:
	 *   file - die Source-URL von dem eingebundenen 'load.js' (siehe oben Class - Exmaple)
	 * Example:
	 *   (start code)
	 *   <script language="javascript" type="text/javascript" src="i-j-library/plugin/autosuggest.js"></script>
	 *   (end)
 	 */
    include: function(file) {
		this.doc.write("<script language=\"javascript\" type=\"text/javascript\" src=\""+file+"\"></script>");
    }
});

/* Section: Initialisierung */
I_J_LIBRARY.implement(new Options);
new I_J_LIBRARY();