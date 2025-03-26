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
namespace VRPayment\Payment\Model;

use Magento\Framework\Exception\LocalizedException;

class CustomerIdManipulationException extends LocalizedException
{
    public function __construct()
    {
        parent::__construct(\__('The payment timed out. Please reload the page and submit the order again.'));
    }
}