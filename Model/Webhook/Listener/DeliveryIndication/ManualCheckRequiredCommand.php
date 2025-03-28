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
namespace VRPayment\Payment\Model\Webhook\Listener\DeliveryIndication;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Webhook listener command to handle delivery indications where a manual check is required.
 */
class ManualCheckRequiredCommand extends AbstractCommand
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
     * @param mixed $entity
     * @param Order $order
     */
    public function execute($entity, Order $order)
    {
        if ($order->getState() != Order::STATE_PAYMENT_REVIEW) {
            $order->setState(Order::STATE_PAYMENT_REVIEW);
            $order->addStatusToHistory(true, \__('A manual decision about whether to accept the payment is required.'));
        }
        $this->orderRepository->save($order);
    }
}