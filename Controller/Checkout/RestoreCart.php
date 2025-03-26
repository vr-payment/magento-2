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
namespace VRPayment\Payment\Controller\Checkout;

use Magento\Framework\DataObject;
use Magento\Framework\App\Action\Context;

/**
 * Frontend controller action to handle checkout failures.
 */
class RestoreCart extends \VRPayment\Payment\Controller\Checkout
{

    public function execute()
    {
        try {
            // Triggers event to validate and restore quote.
            $this->_eventManager->dispatch('vrpayment_validate_and_restore_quote');
        } catch (\Exception $e) {
            // If an error occurs, we display a generic message and redirect to the cart.
            $this->messageManager->addErrorMessage(__('An error occurred while restoring your cart.'));
            return $this->_redirect('checkout/cart');
        }

        // Redirects to the cart or to the path determined by the redirection.
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