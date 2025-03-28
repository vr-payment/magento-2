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
namespace VRPayment\Payment\Model\Webhook\Listener;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use VRPayment\Sdk\Model\Transaction;

/**
 * Abstract webhook listener command for order related entites.
 */
abstract class AbstractOrderRelatedCommand implements CommandInterface
{

    /**
     * Gets the invoice linked to the given transaction.
     *
     * @param Transaction $transaction
     * @param Order $order
     * @return Invoice
     */
    protected function getInvoiceForTransaction(Transaction $transaction, Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Invoice $invoice */
            if (\strpos($invoice->getTransactionId() ?? '', $transaction->getLinkedSpaceId() . '_' . $transaction->getId()) ===
                0 && $invoice->getState() != Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }
    }
}
