
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     n/a
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
var aitexporterProcessor = new Class.create();



aitexporterProcessor.prototype = {

    initialize : function(processorUrl) {

        this.processorUrl = processorUrl;

        this.iterate();

    },

    

    iterate : function() {

        new Ajax.Request(this.processorUrl, {

            method: 'get',

            onSuccess: this.onIterationComplete.bind(this),

            onFailure: this.onIterationFailure.bind(this)

        });

    },



    showMessages : function(messages) {

	    var html = '<ul class="messages">';

        Object.keys(messages).each(function(type){

            messages[type].each(function(txt){

			    html+='<li class="'+type+'-msg"><ul><li>' + txt + '</li></ul></li>';               

            });

        });                

        html = html + '</ul>'

        $('messages').update(html);

    },



    onIterationComplete : function(transport) {

        var data = transport.responseText.evalJSON();



        var button = $('ait_save_and_continue');

        if(!data.continueProcess && button && button.disabled)

        {

            button.removeAttribute('disabled');

            button.removeClassName('disabled');

        }



        $('ait_processor_indicator').replace(data.block);

        

        if(data.continueProcess)

        {

            this.iterate();

        }

        else

        {

            if(data.messages && (data.messages.hasOwnProperty("notice") || data.messages.hasOwnProperty("error") || data.messages.hasOwnProperty("success"))) 

            {

                aitexporterProcessor.prototype.showMessages(data.messages);

            }

            if(data.redirect)

            {

                document.location = data.redirect;

            }

        }

    },

    

    onIterationFailure : function(transport) {

        setTimeout(function() {

            location.reload(true);

        }, 10000);

    }

}