/*browser:true*/
/*global define*/
define(
    [
        'Magento_Vault/js/view/payment/method-renderer/vault'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magento_Vault/payment/form'
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
            }
        });
    }
);
