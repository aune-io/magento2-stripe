/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Vault/js/view/payment/method-renderer/vault',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function ($, Component, messageList, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                paymentIntent: null,
                template: 'Aune_Stripe/payment/vault'
            },

            /**
             * Get last 4 digits of card
             * @returns {String}
             */
            getMaskedCard: function () {
                return this.details.maskedCC;
            },
    
            /**
             * Get expiration date
             * @returns {String}
             */
            getExpirationDate: function () {
                return this.details.expirationDate;
            },
    
            /**
             * Get card type
             * @returns {String}
             */
            getCardType: function () {
                return this.details.type;
            },
    
            /**
             * Get payment method token
             * @returns {String}
             */
            getToken: function () {
                return this.publicHash;
            },

            /**
             * Set payment intent
             * 
             * @param {String} paymentIntent
             */
            setPaymentIntent: function (paymentIntent) {
                this.paymentIntent = paymentIntent;
            },

            /**
             * @returns {*}
             */
            getData: function () {
                var data = {
                    method: this.getCode()
                };

                data['additional_data'] = {};
                data['additional_data']['payment_intent'] = this.paymentIntent;
                data['additional_data']['public_hash'] = this.getToken();

                return data;
            },

            /**
             * Place order click
             */
            placeOrderClick: function () {
                if (!this.isPlaceOrderActionAllowed()) {
                    return;
                }
                this.isPlaceOrderActionAllowed(false);

                var self = this;
                $.get(this.paymentIntentUrl, { public_hash: this.publicHash }, function(response) {

                    if (!response || !response.paymentIntent || !response.paymentIntent.clientSecret) {
                        messageList.addErrorMessage({
                            message: $t('An error occurred generating the payment intent.')
                        });
                        this.isPlaceOrderActionAllowed(true);
                        return;
                    }

                    window.stripe.confirmCardPayment(response.paymentIntent.clientSecret)
                        .then(function (result) {
                            if (result.error) {
                                var message = result.error.message;
                                if (result.error.type == 'validation_error') {
                                    message = $t('Please verify you card information.');
                                }
                                messageList.addErrorMessage({
                                    message: message
                                });
                                return;
                            }

                            self.setPaymentIntent(result.paymentIntent.id);
                            self.placeOrder();
                        });
                });
            }
        });
    }
);
