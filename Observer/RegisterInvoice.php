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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use VRPayment\Payment\Model\Payment\Method\Adapter;
use VRPayment\Payment\Model\Service\Invoice\TransactionService;
use VRPayment\Sdk\Model\TransactionState;

/**
 * Observer to validate and handle the registration of an invoice.
 */
class RegisterInvoice implements ObserverInterface
{
    /**
     *
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @param TransactionService $transactionService
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getInvoice();
        $order = $invoice->getOrder();

        if ($order->getPayment()->getMethodInstance() instanceof Adapter) {
            // Allow creating the invoice if there is no existing one for the order.
            if ($order->getInvoiceCollection()->count() > 1) {
                // Only allow to create a new invoice if all previous invoices of the order have been cancelled.
                if (! $this->canCreateInvoice($order)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        \__('Only one invoice is allowed. To change the invoice, cancel the existing one first.'));
                }

                if (! $invoice->getVrpaymentCapturePending()) {
                    $invoice->setTransactionId(
                        $order->getVrpaymentSpaceId() . '_' .
                        $order->getVrpaymentTransactionId());

                    if (! $order->getVrpaymentInvoiceAllowManipulation()) {
                        // The invoice can only be created by the merchant if the transaction is in state 'AUTHORIZED'.
                        $transaction = $this->transactionService->getTransaction(
                            $order->getVrpaymentSpaceId(),
                            $order->getVrpaymentTransactionId());
                        if ($transaction->getState() != TransactionState::AUTHORIZED) {
                            throw new \Magento\Framework\Exception\LocalizedException(
                                \__('The invoice cannot be created.'));
                        }

                        $this->transactionService->updateLineItems($invoice, $invoice->getGrandTotal());
                    }
                }
            }
        }
    }

    /**
     * Returns whether an invoice can be created for the given order, i.e.
     * there is no existing uncancelled invoice.
     *
     * @param Order $order
     * @return boolean
     */
    private function canCreateInvoice(Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getId() && $invoice->getState() != Invoice::STATE_CANCELED) {
                return false;
            }
        }

        return true;
    }
}