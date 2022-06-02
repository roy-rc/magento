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
                type: 'ctacorriente',
                component: 'Customcode_Ctacorriente/js/view/payment/method-renderer/ctacorriente-method'
            }
        );
        return Component.extend({});
    }
);