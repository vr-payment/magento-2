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
namespace VRPayment\Payment\Model\Webhook\Listener\Transaction;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

/**
 * Webhook listener command to handle declined transactions.
 */
class DeclineCommand extends AbstractCommand
{

    /**
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     *
     * @param \VRPayment\Sdk\Model\Transaction $entity
     * @param Order $order
     */
    public function execute($entity, Order $order)
    {
        if ($order->getState() != Order::STATE_CANCELED) {
            $order->setVrpaymentInvoiceAllowManipulation(true);

            $invoice = $this->getInvoiceForTransaction($entity, $order);
            if ($invoice instanceof Invoice) {
                $invoice->cancel();
                $order->addRelatedObject($invoice);
            }
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();
            $message = $this->appendTransactionToMessage($entity->getLinkedSpaceId() . '_' . $entity->getId(),
                $payment->prependMessage(\__('Registered update about denied payment.')));
            $order->registerCancellation($message, true);
        }
        $this->orderRepository->save($order);
    }

    /**
     * @param mixed $transaction
     * @param string $message
     * @return mixed|string
     */
    private function appendTransactionToMessage($transaction, $message)
    {
        if ($transaction) {
            $txnId = is_object($transaction) ? $transaction->getHtmlTxnId() : $transaction;
            $message .= ' ' . \__('Transaction ID: "%1"', $txnId);
        }
        return $message;
    }
}