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
namespace VRPayment\Payment\Model\Webhook\Listener\Transaction;

use VRPayment\Payment\Model\Webhook\Listener\AbstractOrderRelatedCommand;

/**
 * Abstract webhook listener command to handle transactions.
 */
abstract class AbstractCommand extends AbstractOrderRelatedCommand
{
}