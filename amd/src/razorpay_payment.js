define(['jquery', 'core/ajax'],
    function ($, ajax) {
        return {
            razorpay_payment: function (pluginname, user_id, razorpaykeyid, razorpaykeysecret, instance_id, please_wait_string, buy_now_string) {
                // coupon js code
                console.log(pluginname);
                console.log(user_id);
                console.log(razorpaykeyid);
                console.log(razorpaykeysecret);
                console.log(instance_id);
                console.log(please_wait_string);
                console.log(buy_now_string);
                var buyBtn = $('#payButton');
                var responseContainer = $('#paymentResponse');
                var handleResult = function (result) {
                    console.log(result);
                    if (result.error) {
                        responseContainer.html('<p>' + result.error.message + '</p>');
                    }
                    buyBtn.prop('disabled', false);
                    buyBtn.text(buy_now_string);
                };
                //var stripe = Stripe(publishablekey);
                if (buyBtn) {
                    buyBtn.click(function () {
                        console.log('here');
                        buyBtn.prop('disabled', true);
                        buyBtn.text(please_wait_string);
                        var promises = ajax.call([{
                            methodname: 'moodle_' + pluginname + '_razorpay_js_settings',
                            args: {user_id:user_id, instance_id: instance_id },
                        }]);
                        /*promises[0].then(function (data) {
                            if (data.status) {
                                stripe.redirectToCheckout({
                                    sessionId: data.status,
                                }).then(handleResult);
                            } else {
                                handleResult(data);
                            }
                        }).fail(function (ex) { // do something with the exception 
                            handleResult(ex);
                        });*/
                    });
                }
            }
        };
    });
