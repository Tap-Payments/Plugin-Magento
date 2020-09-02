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
                type: 'knet',
                component: 'Tap_Knet/js/view/payment/method-renderer/gateway-knet'
            }
        );
        return Component.extend({});
    }
 );