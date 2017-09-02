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
                source: null,
                stripe: null,
                cardElement: null,
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
                
                var self = this;
                require([config.sdkUrl], function () {
                    // Initialise Stripe
                    self.stripe = Stripe(config.publishableKey);
                    
                    // Initialise elements
                    var elements = self.stripe.elements();
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
                        'source': this.source
                    }
                };
                
                this.vaultEnabler.visitAdditionalData(data);
                
                return data;
            },

            /**
             * Set source
             * 
             * @param {String} source
             */
            setSource: function (source) {
                this.source = source;
            },
            
            /**
             * Place the order
             * 
             * @param {Object} data
             */
            placeOrderClick: function () {
                if (!this.stripe || !this.cardElement) {
                    console.err('Stripe or CardElement not found');
                    return;
                }
                
                var cardData = { };
                var billingAddress = quote.billingAddress();
                if (billingAddress) {
                    cardData.owner = {
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
                this.stripe.createSource(this.cardElement, cardData)
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
                        
                        self.setSource(result.source.id);
                        self.placeOrder();
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
