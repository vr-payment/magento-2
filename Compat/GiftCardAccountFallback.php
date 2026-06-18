<?php
/**
 * VR Payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR Payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Compat;

/**
 * Stub base used when Magento_GiftCardAccount module is not present.
 * VRPayment\Payment\Compat\GiftCardAccountBase is aliased
 * to this class so that GiftCardAccountWrapper can be declared and reflected
 * during DI compilation without fatal errors when GiftCardAccountManagement
 * from Magento_GiftCardAccount is not isntalled.
 */
class GiftCardAccountFallback
{
}
