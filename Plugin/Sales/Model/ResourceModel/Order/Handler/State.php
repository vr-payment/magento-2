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
namespace VRPayment\Payment\Plugin\Sales\Model\ResourceModel\Order\Handler;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Handler\State as StateHandler;
use VRPayment\Payment\Model\Payment\Method\Adapter;

class State
{

    /**
     * @param StateHandler $stateHandler
     * @param callable $proceed
     * @param Order $order
     * @return Order
     */
    public function aroundCheck(StateHandler $stateHandler, callable $proceed, Order $order)
    {
        if ($order->getState() == Order::STATE_PROCESSING
            && $order->getPayment()->getMethodInstance() instanceof Adapter
            && $this->hasOpenInvoices($order)) {
            if ($order->hasShipments()) {
                if ($order->getStatus() == $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)) {
                    $order->setState(Order::STATE_PROCESSING)->setStatus('shipped_vrpayment');
                }
                return $order;
            } else if ($order->getIsVirtual()) {
                return $order;
            } else {
                return $proceed($order);
            }
        } else {
            return $proceed($order);
        }
    }

    /**
     *
     * @param Order $order
     * @return bool
     */
    protected function hasOpenInvoices(Order $order)
    {
        if ($order->hasInvoices()) {
            /**
             *
             * @var Invoice $invoice
             */
            foreach ($order->getInvoiceCollection() as $invoice) {
                if ($invoice->getState() != Invoice::STATE_PAID) {
                    return true;
                }
            }
        }

        return false;
    }
}