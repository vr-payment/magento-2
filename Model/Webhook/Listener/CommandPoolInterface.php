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
namespace VRPayment\Payment\Model\Webhook\Listener;

/**
 * Webhook listener command pool interface.
 */
interface CommandPoolInterface
{

    /**
     * Retrieves listener.
     *
     * @param string $commandCode
     * @return CommandInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function get($commandCode);
}