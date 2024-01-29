/*browser:true*/
/*global define*/
define(
        [
            'Magento_Checkout/js/view/payment/default',
            'mage/url'
        ],
        function (Component, url) {
            'use strict';

            return Component.extend({
                defaults: {
                    template: 'Easebuzz_Ebp/payment/ebp-form'
                },
                redirectAfterPlaceOrder: false,
                /**
                 * After place order callback
                 */
                afterPlaceOrder: function () {

                    jQuery(function ($) {
                        var self = this;
                        $.ajax({
                            url: url.build('ebp/checkout/start'),
                            type: 'get',
                            dataType: 'json',
                            cache: false,
                            processData: false, // Don't process the files
                            contentType: false, // Set content type to false as jQuery will tell the server its a query string request
                            success: function (data) {   
                                var result=data['html'];
                                
                                if (result.checkout=="0" || result.checkout==null){
                                    $("#easebuzzloader", parent.document).html(result.html);
                                }
                                else{
                                
                                    $.getScript( "https://ebz-static.s3.ap-south-1.amazonaws.com/easecheckout/easebuzz-checkout.js", function() {
                                        
                                        var easebuzzCheckout = new EasebuzzCheckout(result.key, 'prod');
                                        var options = {
                                        access_key: result.access_key , // access key received via Initiate Pay$
                                        onResponse: (response) => {
                                            $.ajax({
                                                url: url.build("ebp/responce/CallbackEbp/"),
                                                // headers: {  'Content-Type':'application/json' },
                                                type: 'post',
                                                data:JSON.stringify(response),
                                                cache: false,
                                                processData: false,
                                                success: function(result){ 
                                                    var resultvar = JSON.parse(result);                                                 
                                                    if(resultvar.status==false){
                                                        var mes = "<h4> Order Id #"+resultvar.order_id+"</h4> <h5 style='color:red'>"+resultvar.message+"</h5>";
                                                        
                                                        $("#easebuzzloader", parent.document).html(mes); 
                                                    }
                                                    else{
                                                        var mes = "<h4> Order Id #"+resultvar.order_id+"</h4> <h5 style='color:green'>"+resultvar.message+"</h5>";
                                                        
                                                        $("#easebuzzloader", parent.document).html(mes); 
                                                    }                                              
                                                }
                                            });
                                    },
                                    theme: "#123456" // color hex
                                    }
                                    easebuzzCheckout.initiatePayment(options);
                                });                               
                            } },
                            error: function (xhr, ajaxOptions, thrownError) {
                                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                            }
                        });
                    });
                },              

            });
        }
);
