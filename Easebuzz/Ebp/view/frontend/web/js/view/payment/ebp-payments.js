/*browser:true*/
/*global define*/
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
                        type: 'ebp',
                        component: 'Easebuzz_Ebp/js/view/payment/method-renderer/ebp-method'
                    }
            );
            /** Add view logic here if needed */
            return Component.extend({});
        }
);
