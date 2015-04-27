/**
* Javascript
* standard-funktionen
*
* @author heiko pfefferkorn
* @copyright 2006 i-fabrik gmbh
* @version $Id: main.js,v 1.29 2007/02/21 14:14:10 heiko Exp $
*
* Im Rahmen der Veranstaltung Sofwareqaulität im SS 2015 des Studigang Wirstschaftsingenieurwesen
* mit Fachrichtung Informationstechnik soll das Postuch ,das ursprünglich von Erik Reuter von der 
* Universität Leipzig entwickelt wurde, auf die Bedürfnisse der EAH Jena angepasst werden.
* 
* Im Rahmen der Vorlesung wird sich Gedanken über einen Anforderungskatalog gemacht, der im Laufe der 
* Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
* zusammengefasst und verwaltet. 
* 
* @author: Tobias Möller, Björn Hoffmann, Maik Tanneberg
*/

    /* fixMsIELeaks
     *
     * Fix some IE memory leaks.
     * http://youngpup.net/2005/0221010713
     */
    function fixMsIELeaks(){
        if( document.all && window.attachEvent ){
            var elProps = [
                "data", "onmouseover", "onmouseout",
                "onmousedown", "onmouseup", "ondblclick",
                "onclick", "onselectstart", "oncontextmenu"
            ];
            var all = d.all;
            for (var i = 0, el; el = all[i]; i++) {
                for (var j = 0, elProp; elProp = elProps[j]; j++) {
                    el[elProp] = null;
                }
            }
        }
    }

    var SITE = {
        start: function(){
            WIDGETS.setMouseOverClass($$('input.input_button'),'input_button_ov');
            WIDGETS.blurAllLinks($$('a'));
            WIDGETS.showTooltips($$('.show_tt'));
            WIDGETS.promptDeleteEntry($$('a.btn_delete_list_entry'),'postbuch_id');
            WIDGETS.promptDeleteEntry($$('a.btn_delete_list_user'),'nutzer_id');

            if($('frm_login')) FORM_LOGIN.start($('frm_login'));
            if($('frm_einrichtung')) FORM_FACILITY.start($('frm_einrichtung'));
            if($('frm_new_msg')) FORM_NEW_MSG.start($('frm_new_msg'));
            if($('frm_options')) FORM_OPTIONS.start($('frm_options'));
            if($('frm_user')) FORM_USER.start($('frm_user'));

            if($('frm_delete') && $$('#blaettern_list li#btn_loeschen a')){
                var btn_temp = $$('#blaettern_list li#btn_loeschen a');
                btn_temp.setProperty('href','#');
                btn_temp.addEvent('mousedown', function(){
                    WIDGETS.promptDeleteEntry($$('#blaettern_list li#btn_loeschen a'));
                    return false;
                })
            }
        },
        end: function() {
            fixMsIELeaks();
        }
    };

    var WIDGETS = {
        openPopUp: function(url,wn,ft,ww,wh,wc){
            if(window.screen)
                if(wc){
                    var wl = (screen.width-ww)/2;
                    var wt = (screen.height-wh)/2;
                    ft+=(ft!='')?',':'';
                    ft+=',left='+wl+',top='+wt;
                }
            window.open(url,wn,ft+((ft!='')?',':'')+'width='+ww+',height='+wh);
        },
        validateEmail: function(s){
            var rx1  = new RegExp("(@.*@)|(\\.\\.)|(@\\.)|(^\\.)");
            var rx2  = new RegExp("^.+\\@(\\[?)[a-zA-Z0-9\\-\\.]+\\.([a-zA-Z]{2,3}|[0-9]{1,3})(\\]?)$");
            var flag = !rx1.test(s) && rx2.test(s);

            return flag;
        },
        blurAllLinks: function(o){
            o.each(function(elm){
                elm.addEvent('click', function(){ this.blur(); });
            });
        },
        setMouseOverClass: function(o,c){
            if(!o) return;

            o.each(function(elm){
                o.addEvent('mouseover', function(){ this.addClass(c); });
                o.addEvent('mouseout', function(){ this.removeClass(c); });
            });
        },
        /* showTooltip
         *
         * MouseOver-Tipps initialisieren
         *
         * @param  object  o  Array aller Objekte die mit einem Tipp versehen werden sollen
         * @return /
         *
         * example: $S('.show_tt');
         */
        showTooltips: function(o){
            if(!o) return;

            var siteTips = new Tips(o, {
                maxTitleChars:100,
                onShow: function(tip){
                    new Fx.Style(tip, 'opacity', {
                        timeOut  : 10,
                        duration : 100
                    }).custom(0,0.9)
                }
            });
        },
        promptDeleteEntry: function(o,id_name){
            if(!o) return;

            o.each(function(elm,i){
                elm.addEvent('click',function(){
                    if(id_name)
                        $(id_name).value = this.getProperty('rel')

                    this.setProperty('href','#');
                    var prompt_delete = new MooPrompt("", "Wollen Sie diesen Eintrag wirklich endgï¿½ltig lï¿½schen? Eine Wiederherstellung ist nicht mï¿½glich.", {
                        width       : 260,
                        height      : 85,
                        buttons     : 2,
                        button1     : 'Abbruch',
                        buttonClass1: 'input_button',
                        onButton1   : function(){  },
                        button2     : 'Ja',
                        buttonClass2: 'input_button',
                        onButton2   : function(){ $('frm_delete').submit(); }
                    });
                    return false;
                });
            });
        },
        checkDate: function(o){
            var v_parse = parseDate(o.getValue, "d.M.y");

            if( v_parse==null ){
                var prompt_date = new MooPrompt("", "Bitte korrigieren Sie Ihre Datumeingabe!", {
                    width       : 260,
                    height      : 55,
                    buttons     : 1,
                    button1     : 'OK',
                    buttonClass1: 'input_button',
                    onButton1   : function(){
                        $(o.id).focus();
                        $(o.id).select();
                    }
                });
            }
        }
    };

    var SITE_ACCORDION = {
        start: function(o_toggler,o_toggler_a,o_stretcher,cmd){
            if( !((o_toggler.length>0 && o_stretcher.length>0) && (o_toggler.length==o_stretcher.length)) )
                return;

            var t = this;
            this.togglers   = o_toggler;
            this.togglers_a = o_toggler_a;
            this.stretchers = o_stretcher;

            if(!cmd)
                t.toggle();
            else
                t.open();
        },
        /* Property: toggle
         *
         * Generieren und Zuweisen der Accordion-Funktionalitï¿½t
         */
        toggle: function(){
            var t = this;
            this.stretchers.each(function(item){
                item.setStyles({
                    height  : '0',
                    overflow: 'hidden'
                });
            });

            this.togglers.each(function(tog, i){
                tog.defaultColor = tog.getStyle('background-color');
            });

            this.acc = new Fx.Accordion(t.togglers, t.stretchers, {duration: 250, opacity: false, start: false, transition: Fx.Transitions.quadOut,
                onActive: function(tog, i){
                    tog.addClass('h4_cur');
                },
                onBackground: function(tog, i){
                    tog.setStyle('background-color', tog.defaultColor);
                    tog.removeClass('h4_cur');
                }
            });

            if( !t.checkHash(this.togglers_a) )
                this.acc.showThisHideOpen(0);
        },
        /* Property: open
         *
         * Inaktives komplett geï¿½ffnetes 'Accordion'. Mousout- und Mouseoverstatus setzen.
         */
        open: function(){
            var t = this;
            /*this.stretchers.each(function(item,i){
                item.addEvent('mouseover', function(){
                    t.togglers[i].addClass('h4_ov');
                });
                item.addEvent('mouseout', function(){
                    t.togglers[i].removeClass('h4_ov');
                });
            });*/
        },
        /* Property: checkHash
         *
         * Aufruf von 'SITE_ACCORDION.activ'. Prï¿½ft URL des Fensters und ï¿½ffnet automatisch bei
         * Vorhandensein eines ï¿½bergebenen Ankernamens den dazugehï¿½rigen Accorion-Inhaltblock.
         *
         * @param   array  o  Array aller 'togglers_a'-Objekte -> siehe 'SITE_ACCORDION.activ'
         * @return  boolean
         */
        checkHash: function(o){
            var t     = this;
            var found = false;

               if( o.length>0 ){
                o.each(function(link, i){
                    link.addEvent('click', function(){ this.blur(); });
                    if(window.location.hash.test(link.hash) ){
                        t.acc.showThisHideOpen(i);
                        found = true;
                    }
                });
               }
            return found;
        }
    };

    var FORM_LOGIN = {
        start: function(f){
            var t = this;
            this.un    = $('login');
            this.pw    = $('passwort');
            f.onsubmit  = function(){
                return t.validate();
            };
        },
        validate: function(){
            var flag_focus  = false;
            var flag_return = true;

            if( this.un.getValue().isEmpty() ){
                $('lbl_login').addClass('fehler');
                flag_return = false;
                if (!flag_focus) {
                    this.un.focus();
                    flag_focus = true;
                }
            }else
                $('lbl_login').removeClass('fehler');

            if( this.pw.getValue().isEmpty() ){
                $('lbl_passwort').addClass('fehler');
                flag_return = false;
                if (!flag_focus) {
                    this.pw.focus();
                    flag_focus = true;
                }
            }else
                $('lbl_passwort').removeClass('fehler');

            return flag_return;
        }
    };

    var FORM_FACILITY = {
        start: function(frm){
            if( !$('einrichtung_id') ) return;

            var s           = this;
            this.f          = frm;
            this.e          = $('einrichtung_id');
            this.e.addEvent('change', function(){
                s.validate();
            });
        },
        validate: function(){
            if( parseInt(this.e.getValue())>0 )
                this.f.submit();
            else
                this.e.options[0].selected = true;

            return true;
        }
    };

    var FORM_NEW_MSG = {
        start: function(f){
            var t = this;
            f.onsubmit = function(){
                return t.validate();
            };

            if( $('datum') )
                $('datum').addEvent('change', function(){ WIDGETS.checkDate(this); });
            if( $('datumextern') )
                $('datumextern').addEvent('change', function(){ WIDGETS.checkDate(this); });
        },
        validate: function(){
            var flag_return = true;
            var flag_focus  = false;

            // datum checken && umschreiben wenn noetig
            if( $('datum') && $('datumextern') ){
            }

            // email checken wenn emailfeld nicht leer
            if( $('email') && ($('email').getValue().isEmpty()==false && WIDGETS.validateEmail($('email').getValue())==false) ){
                $('lbl_email').addClass('fehler');
                if(!flag_focus){
                    $('email').focus();
                    flag_focus = false;
                }
                flag_return = false;
            }else
                $('lbl_email').removeClass('fehler');

            return flag_return;
        }
    };

    var FORM_OPTIONS = {
        start: function(f){
            var t = this;
            f.onsubmit = function(){
                return t.validate();
            };

            $('typ_select').addEvent('change', function(){ t.toggleTyp(); });

            $('passwort').addEvent('blur', function(){
                if(!$('passwort2').getValue().isEmpty() && this.value.isEmpty()){
                    $('lbl_passwort').addClass('fehler');
                    $('lbl_passwort').setStyle('visibility','visible');
                    $('passwort2').value = '';
                    $('passwort').focus();
                }else
                    $('lbl_passwort').removeClass('fehler');
            });

            $('passwort2').addEvent('blur', function(){
                if( this.value!=$('passwort').getValue() ){
                    $('lbl_blank_passwort2').addClass('fehler');
                    $('lbl_blank_passwort2').setStyle('visibility','visible');
                    $('passwort2').focus();
                }else{
                    $('lbl_blank_passwort2').removeClass('fehler');
                    $('lbl_blank_passwort2').setStyle('visibility','hidden');
                }
            });

            if ($('farbe_select') && $('farbe_vorschau'))
	            $('farbe_select').addEvent('change', function(){ $('farbe_vorschau').setStyle('background-color','#'+$('farbe_select').getValue());});

            t.toggleTyp();
        },
        toggleTyp: function(){
            switch($('typ_select').getValue()){
                case 'tage':
                    $('optionen_eintraege').setStyle('display','none');
                    $('optionen_tage').setStyle('display','block');
                    break;
                case 'liste':
                    $('optionen_tage').setStyle('display','none');
                    $('optionen_eintraege').setStyle('display','block');
                    break;
                default:
                    $('optionen_tage').setStyle('display','none');
                    $('optionen_eintraege').setStyle('display','none');
                    break;
            }
        },
        validate: function(){
            var flag_return = true;
            var flag_focus  = false;


            /*
            if( $('email') && ($('email').getValue().isEmpty()==false && WIDGETS.validateEmail($('email').getValue())==false) ){
                $('lbl_email').addClass('fehler');
                if(!flag_focus){
                    $('email').focus();
                    flag_focus = false;
                }
                flag_return = false;
            }else
                $('lbl_email').removeClass('fehler');
            */

            return flag_return;
        }
    };

    var FORM_USER = {
        start: function(f){
            var t = this;
            f.onsubmit = function(){
                return t.validate();
            };

            $('ceinrichtung_id_select').addEvent('change', function(){ t.toggleFacility(); });

            $('passwort').addEvent('blur', function(){
                if(!$('passwort2').getValue().isEmpty() && this.value.isEmpty()){
                    $('lbl_passwort').addClass('fehler');
                    $('lbl_passwort').setStyle('visibility','visible');
                    $('passwort2').value = '';
                    $('passwort').focus();
                }else
                    $('lbl_passwort').removeClass('fehler');
            });

            $('passwort2').addEvent('blur', function(){
                if( this.value!=$('passwort').getValue() ){
                    $('lbl_blank_passwort2').addClass('fehler');
                    $('lbl_blank_passwort2').setStyle('visibility','visible');
                    $('passwort2').focus();
                }else{
                    $('lbl_blank_passwort2').removeClass('fehler');
                    $('lbl_blank_passwort2').setStyle('visibility','hidden');
                }
            });
        },
        toggleFacility: function(){
            switch($('ceinrichtung_id_select').getValue()){
                case '-1':
                    $('optionen_ceinrichtung_neu').setStyle('display','block');
                    break;

                case '0':
                    $('ceinrichtung_id_select').options[0].selected = true;

                default:
                    $('optionen_ceinrichtung_neu').setStyle('display','none');
                    break;

            }
        },
        validate: function(){
            var flag_return = true;
            var flag_focus  = false;
            /*
            if( $('email') && ($('email').getValue().isEmpty()==false && WIDGETS.validateEmail($('email').getValue())==false) ){
                $('lbl_email').addClass('fehler');
                if(!flag_focus){
                    $('email').focus();
                    flag_focus = false;
                }
                flag_return = false;
            }else
                $('lbl_email').removeClass('fehler');
            */

            return flag_return;
        }
    };

    window.addEvent('load', function(){
        if( self.parent.frames.length!=0 )
            self.parent.location = self.location;
    });
    window.addEvent('domready', SITE.start);
    window.addEvent('unload', SITE.end);