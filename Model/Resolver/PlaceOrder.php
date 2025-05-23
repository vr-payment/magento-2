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
namespace VRPayment\Payment\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use VRPayment\Payment\Model\Service\Order\TransactionService as TransactionOrderService;
use VRPayment\Payment\Api\TransactionInfoManagementInterface;

/**
 * Resolver for placing order after payment method has already been set
 */
class PlaceOrder implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var PlaceOrderModel
     */
    private $placeOrder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @var TransactionOrderService
     */
    private $transactionOrderService;

    /**
     *
     * @var TransactionInfoManagementInterface
     */
    private $transactionInfoManagement;

    /**
     * @param GetCartForUser $getCartForUser
     * @param PlaceOrderModel $placeOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionOrderService $transactionOrderService
     * @param TransactionInfoManagementInterface $transactionInfoManagement
     */
    public function __construct(GetCartForUser $getCartForUser, PlaceOrderModel $placeOrder,
    OrderRepositoryInterface $orderRepository, TransactionOrderService  $transactionOrderService,
    TransactionInfoManagementInterface $transactionInfoManagement
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->placeOrder = $placeOrder;
        $this->orderRepository = $orderRepository;
        $this->transactionOrderService = $transactionOrderService;
        $this->transactionInfoManagement = $transactionInfoManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['input']['cart_id'];
        $successUrl = $args['input']['success_url'];
        $failureUrl = $args['input']['failure_url'];
        $integrationType = $args['input']['integration_type'];
        $userId = (int)$context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        try {
            $cart = $this->getCartForUser->getCartForCheckout($maskedCartId, $userId, $storeId);
            $orderId = $this->placeOrder->execute($cart, $maskedCartId, $userId);
            $order = $this->orderRepository->get($orderId);

            //This is necessary because before this point, the table db.mage_wallee_payment_transaction_info has
            //the url of success and fail assigned to a record that does not yet have the order id set.
            //This allows the transaction to have the necessary data to make the redirect.
            //And it solves the side effect of not having the order id at the first point where the transaction info is saved.
            $transaction = $this->getTransaction($order);
            $this->setTransactionUrls($transaction, $orderId, $successUrl, $failureUrl);
            $transactionOutput = $this->getTransactionSettings($transaction, $order, $integrationType);

        } catch (LocalizedException $e) {
            throw new LocalizedException(__('Unable to place order: A server error stopped your order from being placed. ' .
            'Please try to place your order again'), $e);
        }

        return [
            'order' => [
                'order_number' => $order->getIncrementId(),
                // @deprecated The order_id field is deprecated, use order_number instead
                'order_id' => $order->getIncrementId()
            ],
            'transaction' => $transactionOutput
        ];
    }

    /**
     * Update transaction urls to redirect the customer after placing the order
     *
     * @param \VRPayment\Sdk\Model\Transaction $transaction
     * @param string $orderId
     * @param string $successUrl
     * @param string $failureUrl
     * @return void
     * @throws LocalizedException
     */
    private function setTransactionUrls($transaction, $orderId, $successUrl, $failureUrl)
    {
        try {
            $this->transactionInfoManagement->setRedirectUrls($transaction, $orderId, $successUrl, $failureUrl);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }
    }

    /**
     * Gets the transaction settings to use their custom payment integration
     *
     * @param \VRPayment\Sdk\Model\Transaction $transaction
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $integrationType
     * @return array<mixed>
     */
    private function getTransactionSettings($transaction, $order, string $integrationType)
    {
        /** @var Order  $order */
        $url = $this->transactionOrderService->getTransactionPaymentUrl($order, $integrationType);

        return [
                'transaction_id' => $transaction->getId(),
                'transaction_state' => $transaction->getState(),
                'payment_url' => $url,
                'integration_type' => $integrationType
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \VRPayment\Sdk\Model\Transaction
     */
    public function getTransaction(Order $order)
    {
        return $this->transactionOrderService->getTransaction(
            $order->getVrpaymentSpaceId(),
            $order->getVrpaymentTransactionId()
        );
    }
}
