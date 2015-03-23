/*	
	Script: form.validator.js
	A css-class based form validation system.
	
	Dependencies:
	Mootools: <Moo.js>, <Utility.js>, <Common.js>, <Element.js>, <Function.js>, <Event.js>, <String.js>, <Fx.Base.js>, 
			<Window.Base.js>, <Fx.Style.js>, <Fx.Styles.js>, <Dom.js>
			
	Authors:
		Aaron Newton, <aaron [dot] newton [at] cnet [dot] com>
		Based on validation.js by Andrew Tetlaw (http://tetlaw.id.au/view/blog/really-easy-field-validation-with-prototype)

	Class: InputValidator
	This class contains functionality to test a field for various criteria and also to generate 
	an error message when that test fails.
	
	Arguments:
	className - a className that this field will be related to (see example below);
	options - an object with name/value pairs.
	
	Options:
	errorMsg - a message to display; see section below for details.
	test - a function that returns true or false
	
	errorMsg:
	The errorMsg option can be any of the following:
		string - the message to display if the field fails validation
		boolean false - do not display a message at all
		function - a function to evaluate that returns either a string or false.
			This function will be passed two parameters: the field being evaluated and
			any properties defined for the validator as a className (see examples below)
	
	test:
	The test option is a function that will be passed the field being evaluated and
	any properties defined for the validator as a className (see example below); this
	function must return true or false.

	Examples:
(start code)
//html code
<input type="text" name="firstName" class="required" id="firstName">
//simple validator
var isEmpty = new InputValidator('required', {
	errorMsg: 'This field is required.',
	test: function(field){
		return ((element.getValue() == null) || (element.getValue().length == 0));
	}
});
isEmpty.test($("firstName")); //true if empty
isEmpty.getError($("firstName")) //returns "This field is required."

//two complex validators
<input type="text" name="username" class="minLength maxLength" validatorProps="{minLength:10, maxLength:100}" id="username">

var minLength = new InputValidator ('minLength', {
	errorMsg: function(element, props){
		//props is {minLength:10, maxLength:100}
		if($type(props.minLength))
			return 'Please enter at least ' + props.minLength + ' characters (you entered ' + element.value.length + ' characters).';
		else return '';
	}, 
	test: function(element, props) {
		//if the value is >= than the minLength value, element passes test
		return (element.value.length >= $pick(props.minLength, 0));
		else return false;
	}
});

minLength.test($('username'));

var maxLength = new InputValidator ('maxLength', {
	errorMsg: function(element, props){
		//props is {minLength:10, maxLength:100}
		if($type(props.maxLength))
			return 'Please enter no more than ' + props.maxLength + ' characters (you entered ' + element.value.length + ' characters).';
		else return '';
	}, 
	test: function(element, props) {
		//if the value is <= than the maxLength value, element passes test
		return (element.value.length <= $pick(props.maxLength, 10000));
		else return false;
	}
});(end)
	*/

var InputValidator = new Class({
	initialize: function(className, options){
		this.options = Object.extend({
			errorMsg: 'Validation failed.',
			test: function(field){return true}
		}, options || {});
		this.className = className;
	},
/*	Property: test
		Tests a field against the validator's rule(s).
		
		Arguments:
		field - the form input to test
		
		Returns:
		true - the field passes the test
		false - it does not pass the test
	*/
	test: function(field){
		if($(field)) return this.options.test($(field), this.getProps(field));
		else return false;
	},
/*	Property: getError
		Retrieves the error message for the validator.
		
		Arguments:
		field - the form input to test
		
		Returns:
		The error message or the boolean false if no message is meant to be returned.
	*/
	getError: function(field){
		var err = this.options.errorMsg;
		if($type(err) == "function") err = err($(field), this.getProps(field));
		return err;
	},
	getProps: function(field){
		if($(field) && $(field).getProperty('validatorProps')){
			try {
				return Json.evaluate($(field).getProperty('validatorProps'));
			}catch(e){ return {}}
		} else {
			return {}
		}
	}
});

/*	Class: FormValidator
		Evalutes an entire form against all the validators that are set up, displaying messages
		and returning a true/false response for the evaluation of the entire form.
		
		An instance of the FormValidator class will test each field and then behave according to
		the options passed in.
		
		Arguments:
		form - the form to evaluate
		options - an object with name/value pairs
		
		Options:
		fieldSelectors - the selector for fields to include in the validation;
				defaults to: "input, select, textarea"
		useTitles - use the titles of inputs for the error message; overrides
				the messages defined in the InputValidators (see <InputValidator>); defaults to false
		evaluateOnSubmit - validate the form when the user submits it; defaults to true
		evaluateFieldsOnBlur - validate the fields when the blur event fires; defaults to true
		onFormValidate - function to execute when the form validation completes; this function
			is passed two arguments: a boolean (true if the form passed validation) and the form element
		onElementValidate - function to execute when an input element is tested; this function
			is passed two arguments: a boolean (true if the form passed validation) and the input element
		
		Example:
(start code)var myFormValidator = new FormValidator($('myForm'), {
	onFormValidate: myFormHandler,
	useTitles: true
});(end)

		Note: FormValidator must be configured with <Validator> objects; see below for details as well as a list of built-in validators. Each <Validator> will be applied to any input that matches its className within the elements of the form that match the fieldSelectors option.
	*/
var FormValidator = new Class({
	initialize: function(form, options){
		this.options = Object.extend({
			fieldSelectors:"input, select, textarea",
			useTitles:false,
			evaluateOnSubmit:true,
			evaluateFieldsOnBlur: true,
			onFormValidate: function(isValid, form){},
			onElementValidate: function(isValid, field){}
		}, options || {});
		try {
			this.form = $(form);
			if(this.options.evaluateOnSubmit) this.form.addEvent('submit', this.onSubmit.bind(this));
			if(this.options.evaluateFieldsOnBlur) this.watchFields();
		}catch(e){//console.log('error: %s', e);
		}
	},
	watchFields: function(){
		try{
			this.form.getElementsBySelector(this.options.fieldSelectors).each(function(el){
				el.addEvent('blur', this.validateField.pass(el, this));
			}, this);
		}catch(e){//console.log('error: %s', e);
		}
	},
	onSubmit: function(event){
		if(!this.validate()) new Event(event).stop();
	},
/*	Property: reset
		Removes all the error messages from the form.
	*/
	reset: function() {
		this.form.getElementsBySelector(this.options.fieldSelectors).each(this.resetField, this);
	}, 
/*	Property: validate
		Validates all the inputs in the form; note that this function is called on submit unless
		you specify otherwise in the options.
	*/
	validate : function() {
		var result = this.form.getElementsBySelector(this.options.fieldSelectors).map(function(field) { return this.validateField(field); }, this);
		result = result.every(function(val){
			return val;
		});
		this.options.onFormValidate(result, this.form);
		return result;
	},
/*	Property: validateField
		Validates the value of a field against all the validators.
		
		Arguments:
		field - the input element to evaluate
	*/
	validateField: function(field){
		field = $(field);
		var result = true;
		if(field){
			var validators = field.className.split(" ").some(function(cn){
				return FormValidator.getValidator(cn);
			});
			result = field.className.split(" ").map(function(className){
				var test = this.test(className,field);
				return test;
			}, this);
			result = result.every(function(val){
				return val;
			});
			if(validators){
				if(result) field.addClass('validation-passed').removeClass('validation-failed');
				else field.addClass('validation-failed').removeClass('validation-passed');
			}
		}
		return result;
	},
	getPropName: function(className){
		return '__advice'+className;
	},
/*	Property: test
		Tests a field against a specific validator.
		
		Arguments:
		className - the className associated with the validator
		field - the input element
	*/
	test: function(className, field){
		field = $(field);
		var isValid = true;
		if(field) {
			var validator = FormValidator.getValidator(className);
			if(validator && this.isVisible(field)) {
				isValid = validator.test(field);
				//if the element is visible and it failes to validate
				if(!isValid && validator.getError(field)){
					var advice = this.makeAdvice(className, field, validator.getError(field));
					this.showAdvice(className, field);
				} else this.hideAdvice(className, field);
				this.options.onElementValidate(isValid, field);
			}
		}
		return isValid;
	},
	showAdvice: function(className, field){
		var advice = this.getAdvice(className, field);
		if(advice && !field[this.getPropName(className)] && (advice.getStyle('display') == "none" || advice.getStyle('visiblity') == "hidden" || advice.getStyle('opacity')==0)){
			field[this.getPropName(className)] = true;
			advice.setStyles({
				'display':'block',
				'visibility':'hidden'
			});
			var h = advice.getSize().scrollSize.y;
			var pt = advice.getStyle('padding-top').toInt();
			var pb = advice.getStyle('padding-bottom').toInt();
			var mt = advice.getStyle('margin-top').toInt();
			var mb = advice.getStyle('margin-bottom').toInt();
			h = h-pt-pb;
			advice.setStyles({
				'opacity':0,
				'height':'0px',
				'padding-top':'0px',
				'padding-bottom':'0px',
				'margin-top':'0px',
				'margin-bottom':'0px'
			}).effects().start({
				'height':h,
				'opacity':1,
				'padding-top':pt,
				'padding-bottom':pb,
				'margin-top':mt,
				'margin-bottom':mb
			});
		}
	},
	hideAdvice: function(className, field){
		var advice = this.getAdvice(className, field);
		if(advice && field[this.getPropName(className)]) {
			field[this.getPropName(className)] = false;
			var h = advice.getSize().scrollSize.y;
			var pt = advice.getStyle('padding-top').toInt();
			var pb = advice.getStyle('padding-bottom').toInt();
			var mt = advice.getStyle('margin-top').toInt();
			var mb = advice.getStyle('margin-bottom').toInt();
			h = h-pt-pb;
			advice.effects().start({
				'height':0,
				'opacity':0,
				'padding-top':0,
				'padding-bottom':0,
				'margin-top':0,
				'margin-bottom':0
			}).chain(function(){
				advice.setStyles({
					'display':'none',
					'height':h+'px',
					'padding-top':pt+'px',
					'padding-bottom':pb+'px',
					'margin-top':mt+'px',
					'margin-bottom':mb+'px'
				});
			});
		}
	},
	isVisible : function(field) {
		while(field.tagName != 'BODY') {
			if($(field).getStyle('display') == "none") return false;
			field = field.parentNode;
		}
		return true;
	},
	getAdvice: function(className, field) {
		return $('advice-' + className + '-' + this.getFieldId(field))
	},
	makeAdvice: function(className, field, error){
		var errorMsg = this.options.useTitles ? $pick(field.title, error):error;
		var advice = this.getAdvice(className, field);
		if(!advice){
			advice = new Element('div').addClass('validation-advice').setProperty(
				'id','advice-'+className+'-'+this.getFieldId(field)).setStyle('display','none').appendText(errorMsg);
			switch (field.type.toLowerCase()) {
				case 'radio':
					var p = $(field.parentNode);
					if(p) {
						p.adopt(advice);
						break;
					}
				default: advice.injectAfter($(field));
		  };
		} else{
			advice.setHTML(errorMsg);
		}
		return advice;
	},
	getFieldId : function(field) {
		return field.id ? field.id : field.id = "input_"+field.name;
	},
/*	Property: resetField
		Removes all the error messages for a specific field.
		
		Arguments:
		field - the field to reset.
	*/
	resetField: function(field) {
		field = $(field);
		if(field) {
			var cn = field.className.split(" ");
			cn.each(function(className) {
				var prop = this.getPropName(className);
				if(field[prop]) this.hideAdvice(className, field);
				field.removeClass('validation-failed');
				field.removeClass('validation-passed');
			}, this);
		}
	}
});

/*	Section: FormValidator global functions
		These functions are available to the <FormValidator> object itself, not instances of it.
		Use these functions to add validators to the FormValidator object, which will be available
		to all instances of the FormValidator class.
	*/
Object.extend(FormValidator, {
/*	Property: validators
		An array of <Validator> objects.
	*/
	validators:[],
/*	Property: add
		Adds a new form validator to the FormValidator object.
		
		Arguments:
		className - the className associated with the validator
		options - the <Validator> options (errorMsg and test)
		Example:
(start code)
FormValidator.add('isEmpty', {
	errorMsg: 'This field is required',
	test: function(element){
		if(element.value.length ==0) return false;
		else return true;
	}
});
	*/
	add : function(className, options) {
		this.validators[className] = new InputValidator(className, options);
	},
/*	Property: addAllThese
		An array of InputValidator configurations (see <FormValidator.add> above).
		
		Example:
(start code)
FormValidator.addAllThese([
	['className1', {errorMsg: ..., test: ...}],
	['className2', {errorMsg: ..., test: ...}],
	['className3', {errorMsg: ..., test: ...}],
]);
	*/
	addAllThese : function(validators) {
		$A(validators).each(function(validator) {
			this.add(validator[0], validator[1]);
		}, this);
	},
	getValidator: function(className){
		return FormValidator.validators[className] = $pick(FormValidator.validators[className], false);
	}
});


/*	Section: Included InputValidators
		Here is are the validators that are included in this libary. Add the className to
		any input and then create a new <FormValidator> and these will automatically be
		applied. See <FormValidator.add> on how to add your own.

		Property: IsEmpty
		Evalutes if the input is empty; this is a utility validator, see <FormValidator.required>.
		
		Error Msg - returns false (no message)
			*/
FormValidator.add('IsEmpty', {
	errorMsg: false,
	test: function(element) { 
		if(element.type == "select-one"||element.type == "select")
			return !(element.selectedIndex >= 0 && element.options[element.selectedIndex].value != "");
		else
			return ((element.getValue() == null) || (element.getValue().length == 0));
	}
});


FormValidator.addAllThese([
/*	Property: required
		Displays an error if the field is empty.
		
		Error Msg - "This field is required"			
	*/
	['required', {
		errorMsg: function(element){return 'This field is required.'}, 
		test: function(element) { 
			return !FormValidator.getValidator('IsEmpty').test(element); 
		}
	}],
/*	Property: minLength
		Displays a message if the input value is less than the supplied length.
		
		Error Msg - Please enter at least [defined minLength] characters (you entered [input length] characters)
		
		Note:
		You must add this className AND properties for it to your input.
	
		Example:
		><input type="text" name="username" class="minLength props{minLength:10}" id="username">
	*/
	['minLength', {
		errorMsg: function(element, props){
			if($type(props.minLength))
				return 'Please enter at least ' + props.minLength + ' characters (you entered ' + element.getValue().length + ' characters).';
			else return '';
		}, 
		test: function(element, props) {
			if($type(props.minLength)) return (element.getValue().length >= $pick(props.minLength, 0));
			else return true;
		}
	}],
/*	Property: maxLength
		Displays a message if the input value is less than the supplied length.
		
		Error Msg - Please enter no more than [defined maxLength] characters (you entered [input length] characters)
		
		Note:
		You must add this className AND properties for it to your input.
		
		Example:
		><input type="text" name="username" class="maxLength props{maxLength:100}" id="username">
	*/
	['maxLength', {
		errorMsg: function(element, props){
			//props is {maxLength:10}
			if($type(props.maxLength))
				return 'Please enter no more than ' + props.maxLength + ' characters (you entered ' + element.getValue().length + ' characters).';
			else return '';
		}, 
		test: function(element, props) {
			//if the value is <= than the maxLength value, element passes test
			return (element.getValue().length <= $pick(props.maxLength, 10000));
		}
	}],
/*	Property: validate-number
		Validates that the entry is a number.
		
		Error Msg - 'Please enter a valid number in this field.'
	*/	
	['validate-number', {
		errorMsg: 'Please enter a valid number in this field.',
		test: function(element) {
				return FormValidator.getValidator('IsEmpty').test(element) || ((!isNaN(element.getValue()) && !/^\s+$/.test(element.getValue())));
		}
	}],
/*	Property: validate-digits
		Validates that the entry contains only numbers

		Error Msg - 'Please use numbers only in this field. Please avoid spaces or other characters such as dots or commas.'
	*/
	['validate-digits', {
		errorMsg: 'Please use numbers only in this field. Please avoid spaces or other characters such as dots or commas.', 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || 
				(!/[^a-zA-Z]/.test(element.getValue()) && /[\d]/.test(element.getValue()));
		}
	}],
/*	Property: validate-alpha
		Validates that the entry contains only letters 

		Error Msg - 'Please use letters only (a-z) in this field.'
	*/
	['validate-alpha', {
		errorMsg: 'Please use letters only (a-z) in this field.', 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) ||  /^[a-zA-Z]+$/.test(element.getValue())
		}
	}],
/*	Property: validate-alphanum
		Validates that the entry is letters and numbers only

		Error Msg - 'Please use only letters (a-z) or numbers (0-9) only in this field. No spaces or other characters are allowed.'
	*/
	['validate-alphanum', {
		errorMsg: 'Please use only letters (a-z) or numbers (0-9) only in this field. No spaces or other characters are allowed.', 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || !/\W/.test(element.getValue())
		}
	}],
/*	Property: validate-date
		Validates that the entry parses to a date.

		Error Msg - 'Please use this date format: mm/dd/yyyy. For example 03/17/2006 for the 17th of March, 2006.'
	*/
	['validate-date', {
		errorMsg: 'Please use this date format: mm/dd/yyyy. For example 03/17/2006 for the 17th of March, 2006.',
		test: function(element) {
			if(FormValidator.getValidator('IsEmpty').test(element)) return true;
	    var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
	    if(!regex.test(element.getValue())) return false;
	    var d = new Date(element.getValue().replace(regex, '$1/$2/$3'));
	    return (parseInt(RegExp.$1, 10) == (1+d.getMonth())) && 
        (parseInt(RegExp.$2, 10) == d.getDate()) && 
        (parseInt(RegExp.$3, 10) == d.getFullYear() );
		}
	}],
/*	Property: validate-email
		Validates that the entry is a valid email address.

		Error Msg - 'Please enter a valid email address. For example fred@domain.com .'
	*/
	['validate-email', {
		errorMsg: 'Please enter a valid email address. For example fred@domain.com .', 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/.test(element.getValue());
		}
	}],
/*	Property: validate-url
		Validates that the entry is a valid url

		Error Msg - 'Please enter a valid URL.'
	*/
	['validate-url', {
		errorMsg: 'Please enter a valid URL.', 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(element.getValue());
		}
	}],
/*	Property: validate-date-au
		Validates that the entry matches dd/mm/yyyy.

		Error Msg - 'Please use this date format: dd/mm/yyyy. For example 17/03/2006 for the 17th of March, 2006.'
	*/
	

	['validate-date-au', {
		errorMsg: 'Please use this date format: dd/mm/yyyy. For example 17/03/2006 for the 17th of March, 2006.',
		test: function(element) {
			if(FormValidator.getValidator('IsEmpty').test(element)) return true;
	    var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
	    if(!regex.test(element.getValue())) return false;
	    var d = new Date(element.getValue().replace(regex, '$2/$1/$3'));
	    return (parseInt(RegExp.$2, 10) == (1+d.getMonth())) && 
        (parseInt(RegExp.$1, 10) == d.getDate()) && 
        (parseInt(RegExp.$3, 10) == d.getFullYear() );
		}
	}],
/*	Property: validate-currency-dollar
		Validates that the entry matches any of the following:
			- [$]1[##][,###]+[.##]
			- [$]1###+[.##]
			- [$]0.##
			- [$].##
		
		Error Msg - 'Please enter a valid $ amount. For example $100.00 .'
	*/
	['validate-currency-dollar', {
		errorMsg: 'Please enter a valid $ amount. For example $100.00 .', 
		test: function(element) {
			// [$]1[##][,###]+[.##]
			// [$]1###+[.##]
			// [$]0.##
			// [$].##
			return FormValidator.getValidator('IsEmpty').test(element) ||  /^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/.test(element.getValue());
		}
	}],
/*	Property: validate-one-required
		Validates that all the entries within the same node are not empty.

		Error Msg - 'Please enter something for at least one of the above options.'
		
		Note:
		This validator will get the parent element for the input and then check all its children.
		To use this validator, enclose all the inputs you want to group in another element (doesn't
		matter which); you only need apply this class to *one* of the elements.
		
		Example:
(start code)
<div>
	<input ....>
	<input ....>
	<input .... className="validate-one-required">
</div>(end)
	*/
	['validate-one-required', {
		errorMsg: 'Please enter something for at least one of the above options.', 
		test: function (element) {
			var p = element.parentNode;
			var options = p.getElements('input');
			return $A(options).some(function(el) {
				return el.getValue();
			});
		}
	}]
]);

/* do not edit below this line */   
/* Section: Change Log 

$Source: /usr/local/cvsroot/uni_postbuch/j-scib/widget/validate-form.js,v $
$Log: validate-form.js,v $
Revision 1.1  2007/02/21 14:46:03  heiko
*** empty log message ***

Revision 1.3  2007/01/26 05:48:03  newtona
docs update

Revision 1.2  2007/01/22 22:00:15  newtona
numerous bug fixes to modalizer, stickywin, and popupdetails
updated for mootools 1.0
fixed date validation in form.validator

Revision 1.1  2007/01/19 01:22:05  newtona
*** empty log message ***


*/
