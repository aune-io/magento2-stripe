/*browser:true*/
/*global define*/
/*global Stripe*/
define(
    [
        'underscore',
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        _,
        $,
        ko,
        Component,
        quote,
        VaultEnabler,
        messageList,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aune_Stripe/payment/cc-form',
                stripe: null,
                cardElement: null,
                paymentIntent: null,
                paymentIntentUrl: null,
                fieldErrorMessages: {
                    card: ko.observable(false),
                    expiry: ko.observable(false),
                    cvc: ko.observable(false)
                }
            },
            
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
    
                return this;
            },
            
            /**
             * Initialize Stripe element
             */
            initStripe: function () {
                var config = window.checkoutConfig.payment[this.getCode()];
                if (!config) {
                    return;
                }

                this.paymentIntentUrl = config.paymentIntentUrl;

                var self = this;
                require([config.sdkUrl], function () {
                    // Initialise Stripe
                    window.stripe = Stripe(config.publishableKey);
                    
                    // Initialise elements
                    var elements = window.stripe.elements();
                    self.cardElement = elements.create('cardNumber');
                    self.cardElement.mount('#' + self.getCode() + '_cc_number');
                    self.cardElement.on('change', self.onFieldChange('card'));
                    
                    var cardExpiry = elements.create('cardExpiry');
                    cardExpiry.mount('#' + self.getCode() + '_expiry');
                    cardExpiry.on('change', self.onFieldChange('expiry'));
                    
                    var cardCvc = elements.create('cardCvc');
                    cardCvc.mount('#' + self.getCode() + '_cc_cvc');
                    cardCvc.on('change', self.onFieldChange('cvc'));
                });
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                return this.getCode() === this.isChecked();
            },
            
            /**
             * Return field's error message observable
             */
            getErrorMessageObserver: function (field) {
                return this.fieldErrorMessages[field];
            },
            
            /**
             * Return field change event handler
             */
            onFieldChange: function (fieldName) {
                var errorMessage = this.fieldErrorMessages[fieldName];
                return function (event) {
                    errorMessage(
                        event.error ? event.error.message : false
                    );
                };
            },

            /**
             * Get data
             *
             * @returns {Object}
             */
            getData: function () {
                var data = {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_intent': this.paymentIntent
                    }
                };
                
                this.vaultEnabler.visitAdditionalData(data);
                
                return data;
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
             * Place the order
             * 
             * @param {Object} data
             */
            placeOrderClick: function () {
                if (!window.stripe || !this.cardElement) {
                    console.err('Stripe or CardElement not found');
                    return;
                }
                
                var data = {
                    payment_method: {
                        card: this.cardElement
                    }
                };
                
                var billingAddress = quote.billingAddress();
                if (billingAddress) {
                    data.payment_method.billing_details = {
                        name: billingAddress.firstname + ' ' + billingAddress.lastname,
                        phone: billingAddress.telephone,
                        address: {
                            line1: billingAddress.street[0],
                            line2: billingAddress.street.length > 1 ? billingAddress.street[1] : null,
                            city: billingAddress.city,
                            state: billingAddress.region,
                            postal_code: billingAddress.postcode,
                            country: billingAddress.countryId,
                        }
                    };
                }

                var self = this;
                $.get(this.paymentIntentUrl, {}, function(response) {

                    if (!response || !response.paymentIntent || !response.paymentIntent.clientSecret) {
                        messageList.addErrorMessage({
                            message: $t('An error occurred generating the payment intent.')
                        });
                        return;
                    }

                    window.stripe.confirmCardPayment(response.paymentIntent.clientSecret, data)
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
            },

            /**
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },
            
            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            }
        });
    }
);
