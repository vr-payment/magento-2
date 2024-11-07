<?php
/**
 * VR payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Plugin\Customer\Model;

use Magento\Checkout\Model\Session as CheckoutSession;

class AccountManagement
{

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param string $customerEmail
     * @param int $websiteId
     * @return void
     */
    public function beforeIsEmailAvailable(\Magento\Customer\Model\AccountManagement $subject, $customerEmail,
        $websiteId = null)
    {
        $this->checkoutSession->setVRPaymentCheckoutEmailAddress($customerEmail);
    }
}