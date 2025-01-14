/**
 * VRPay Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPay (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
define([
	'jquery',
	'uiComponent',
	'Magento_Checkout/js/model/payment/renderer-list'
], function(
	$,
	Component,
	rendererList
) {
	'use strict';
	
	// Loads the VRPay Javascript File
	if (window.checkoutConfig.vrpayment.javascriptUrl) {
		$.getScript(window.checkoutConfig.vrpayment.javascriptUrl);
	}
	
	// Loads the VRPay Lightbox File
	if (window.checkoutConfig.vrpayment.lightboxUrl) {
		$.getScript(window.checkoutConfig.vrpayment.lightboxUrl);
	}
	
	// Registers the VRPay payment methods
	$.each(window.checkoutConfig.payment, function(code){
		if (code.indexOf('vrpayment_payment_') === 0) {
			rendererList.push({
			    type: code,
			    component: 'VRPayment_Payment/js/view/payment/method-renderer/vrpayment-method'
			});
		}
	});
	
	return Component.extend({});
});