/*
Script: ajax.cnet.js
This is an extension to the Ajax class in the <http://mootools.net> library.

Dependancies:
     mootools - <Moo.js>, <String.js>, <Array.js>, <Function.js>, <Element.js>
     cnet libraries - <dbug.js>

Author:
    Aaron Newton, <aaron [dot] newton [at] cnet [dot] com>

        Additional Options:
        onFailure - This extention adds the ability to pass in an _onFailure_ function
        that will fire if the ajax call fails. Additionally, it will check
        the response to see if the page failed. it will look for the
        presence of "COMPONENT_RESPONSE_CODE" in the returned document
        and if presence, look for "COMPONENT_RESPONSE_CODE=200". This will
        allow you to detect when our server returns our page not found
        document, as our server doesn't post a 404 header with it.

        fireNow - Finally, an additional option can be passed in: fireNow: true/false
        if true, the request will fire automatically. This is really a legacy
        thing. Instead you should do this:

        > var myAjax = new Ajax(url, {options}).request();

        to fire automatically. You do not have to assign the object (var myAjax)
        but you'll need it if you want to query the status of it later.

        Example instantiation:
        >new Ajax(myurl, {onComplete: myFunction, onFailure: myErrorHandler, method: 'get'});

        See Also: <Ajax>
*/
Ajax.implement({
    initialize: function(url, options){
        this.setOptions(options);
        this.options.postBody = $set(this.options.postBody, '');
        this.url = url;
        this.transport = this.getTransport();
        this.options.fireNow = $set(this.options.fireNow, true);
        this.tried = 0;
        if(this.options.fireNow) this.autoRequest.delay(200, this);
    },
    autoRequest: function(){
        if(this.tried < 10 && this.responseIsFailure()){
            this.tried++;
            (function(){
                try{
                    this.request();
                }catch(e){
                    dbug.log("error; auto fire trying again momentarily. error: %s", e);
                    this.autoRequest();
                }
            }).delay(50, this);
        } else
            dbug.log('unable to fire ajax automatically');
    },
    responseIsSuccess: function(status){
        try {
            if(this.transport.readyState != 4 ||
                 this.transport.status == "undefined" ||
                (this.transport.status < 200 || this.transport.status >= 300))
                    return false;
            if((this.transport.responseText.indexOf("COMPONENT_RESPONSE_CODE")<0 ||
                    this.transport.responseText.indexOf("COMPONENT_RESPONSE_CODE=200")>=0) &&
                    this.transport.responseText.indexOf("<title>Page Not Found")<0) {
                    dbug.log('ajax request successful');
                    return true;
            }
            dbug.log('ajax request failed');
            return false;
        } catch(e) {
            return false;
        }
    },
    responseIsFailure: function(){
        return !this.responseIsSuccess();
    },
    onStateChange: function(){
        if (this.transport.readyState == 4 && this.responseIsSuccess()){
            if (this.options.update) $(this.options.update).setHTML(this.transport.responseText);
            this.options.onComplete.pass([this.transport.responseText, this.transport.responseXML], this).delay(20);
            if (this.options.evalScripts) this.evalScripts.delay(30, this);
            this.transport.onreadystatechange = Class.empty;
            this.callChain();
        } else if(this.transport.readyState == 4 && this.responseIsFailure()) {
            if($type(this.options.onFailure)=='function') this.options.onFailure.pass(this.transport, this).delay(20);
        }
    }
});
/* do not edit below this line */
/* Section: Change Log

$Source: /usr/local/cvsroot/uni_postbuch/j-scib/extend/ajax.js,v $
$Log: ajax.js,v $
Revision 1.1  2007/02/21 14:46:03  heiko
*** empty log message ***

Revision 1.1  2007/01/15 12:29:10  heiko
*** empty log message ***

Revision 1.2  2006/12/19 15:03:33  heiko
*** empty log message ***

Revision 1.1  2006/12/15 14:37:35  heiko
*** empty log message ***

Revision 1.3  2006/11/17 02:49:14  newtona
modified cnet.ajax to handle automatic firing a little more gracefully
modified dom.js to fix a bug with css selectors

Revision 1.2  2006/11/02 21:34:00  newtona
Added cvs footer


*/
