define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'aune_stripe',
                component: 'Aune_Stripe/js/view/payment/method-renderer/cc-form'
            }
        );

        return Component.extend({});
    }
);
