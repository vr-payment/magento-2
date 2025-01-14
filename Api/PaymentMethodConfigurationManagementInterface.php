<?php
/**
 * VRPay Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPay (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Api;

/**
 * Payment method configuration management interface.
 *
 * @api
 */
interface PaymentMethodConfigurationManagementInterface
{

    /**
     * Synchronizes the payment method configurations from VRPay.
     * @return void
     */
    public function synchronize();

    /**
     * Updates the payment method configuration information.
     *
     * @param \VRPayment\Sdk\Model\PaymentMethodConfiguration $configuration
     * @return void
     */
    public function update(\VRPayment\Sdk\Model\PaymentMethodConfiguration $configuration);
}
