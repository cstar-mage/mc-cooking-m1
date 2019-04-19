/*document.observe("dom:loaded", function() {
    $('release_time').addClassName('validate-delivery-time');
});*/
/*varienForm.prototype.submit = function(url){
    if (typeof varienGlobalEvents != undefined) {
        varienGlobalEvents.fireEvent('formSubmit', this.formId);
    }
    this.errorSections = $H({});
    this.canShowError = true;
    this.submitUrl = url;
    if(this.validator && this.validator.validate()){
        if(this.validationUrl){
            this._validate();
        }
        else{
            this._submit();
        }
        return true;
    }
    return false;
}*/
Validation.add('validate-delivery-time', 'Please enter a valid time, for example: 12:00 PM', function(v) {
    console.log(/^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/.test(v));
    return /^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/.test(v);
});