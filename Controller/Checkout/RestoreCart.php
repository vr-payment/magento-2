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
namespace VRPayment\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\App\Action\Context;

/**
 * Frontend controller action to handle checkout failures.
 */
class RestoreCart extends \VRPayment\Payment\Controller\Checkout
{

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(Context $context, CheckoutSession $checkoutSession)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $this->checkoutSession->restoreQuote();

        return $this->_redirect($this->getFailureRedirectionPath());
    }

    /**
     * Gets the path to redirect the customer to.
     *
     * @return string
     */
    private function getFailureRedirectionPath()
    {
        $response = new DataObject();
        $response->setPath('checkout/cart');
        $this->_eventManager->dispatch('vrpayment_checkout_failure_redirection_path',
            [
                'response' => $response
            ]);
        return $response->getPath();
    }

}