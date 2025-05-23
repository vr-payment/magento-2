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
use VRPayment\Sdk\Model\TransactionState;

/**
 * Webhook listener command to handle voided transactions.
 */
class VoidedCommand extends AbstractCommand
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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $payment->registerVoidNotification();

        $invoice = $this->getInvoiceForTransaction($entity, $order);
        if ($invoice) {
            $order->setVrpaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);
        }

        if ($entity->getState() == TransactionState::VOIDED) {
            $order->setState(Order::STATE_CANCELED);
            $order->addStatusToHistory('canceled',
                \__('The order has been canceled.'));
        }
        $this->orderRepository->save($order);
    }
}