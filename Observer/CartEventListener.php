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
namespace VRPayment\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Observer to listen all the changes made to the cart.
 */
class CartEventListener implements ObserverInterface
{

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(LoggerInterface $logger, CheckoutSession $checkoutSession)
    {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * This event listener was created appositely to clear the checkout session whenever the cart is changed.
     * By cleaning the checkout session, we impose to call the VRPayment Portal and update the transaction
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->debug("CART-EVENT-LISTENER::execute - Clear session");
        try{
            $this->checkoutSession->unsTransaction();
            $this->checkoutSession->unsPaymentMethods();
            $this->checkoutSession->unsPaymentUrl();
        } catch (\Exception $ignored){}
    }
}
