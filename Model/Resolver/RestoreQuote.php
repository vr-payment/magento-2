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

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\OrderRepositoryInterface;

class RestoreQuote implements ResolverInterface
{
    /**
     *
     * @var Session
     */
    private $customerSession;

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteIdService;

    /**
     *
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param GetCustomer $getCustomer
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteIdService
     * @param ManagerInterface $eventManager
     * @param LoggerInterface $logger
     */
    public function __construct(Session $customerSession, CheckoutSession $checkoutSession,GetCustomer $getCustomer,
    CartRepositoryInterface $cartRepository, OrderRepositoryInterface $orderRepository,
    MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteIdService, ManagerInterface $eventManager, LoggerInterface $logger) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->getCustomer = $getCustomer;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->maskedQuoteIdToQuoteIdService = $maskedQuoteIdToQuoteIdService;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        try {
            $cartIdMasked = $args['input']['cart_id'];

            if ($context->getUserType() === UserContextInterface::USER_TYPE_GUEST) {
                return $this->restoreGuestQuote($cartIdMasked);
            }

            if ($context->getUserType() !== UserContextInterface::USER_TYPE_GUEST) {
                /** @var ContextInterface $context $customerId */
                $customerId = $this->getCustomerIdFromContext($context);
                return $this->restoreCustomerQuote($cartIdMasked, $customerId);
            }
            return ['result' => 'There is no cart available'];
        } catch (NoSuchEntityException|\Exception $e) {
            $this->logger->critical($e);
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

    /**
     * Restores a customer's quote from a cart id
     *
     * @param string $cartIdMasked
     * @param string $customerId
     * @return array
     * @throws LocalizedException
     */
    private function restoreCustomerQuote(string $cartIdMasked, string $customerId)
    {
        try {
            // Convert the masked ID to the real quote ID
            $quoteId = $this->maskedQuoteIdToQuoteIdService->execute($cartIdMasked);

            // Get the quote using the actual ID
            /** @var Quote $quote */
            $quote = $this->cartRepository->get($quoteId);
            $order = $this->getOrderByQuote($quote);

            //some validations
            $this->guardQuoteBelongsToCurrentCustomer($order, $customerId);
            $this->guardQuoteIsStillActive($quote);

            return $this->restoreQuote($cartIdMasked, $quote, $order);
        } catch (NoSuchEntityException|\Exception $e) {
            return ['result' => 'KO. ' . $e->getMessage()];
        }
    }

    /**
     * Restores a guest's quote from a cart id
     *
     * @param string $cartIdMasked
     * @return array
     * @throws LocalizedException
     */
    private function restoreGuestQuote(string $cartIdMasked)
    {
        try {
            // Convert the masked ID to the real quote ID
            $quoteId = $this->maskedQuoteIdToQuoteIdService->execute($cartIdMasked);

            // Get the quote using the actual ID
            /** @var Quote $quote */
            $quote = $this->cartRepository->get($quoteId);
            $order = $this->getOrderByQuote($quote);

            return $this->restoreQuote($cartIdMasked, $quote, $order);
        } catch (NoSuchEntityException|\Exception $e) {
            return ['result' => 'KO. ' . $e->getMessage()];
        }
    }

    /**
     * Get an order by quote
     *
     * @param Quote $quote
     * @return OrderInterface|null
     * @throws \Exception
     */
    public function getOrderByQuote(Quote $quote)
    {
        $orderId = $quote->getReservedOrderId();

        if (empty($orderId)) {
            throw new \Exception(__('The cart does not have an associated order'));
        }

        return $this->orderRepository->getOrderById($orderId);
    }

    /**
     * Check if quote belongs to the current customer
     *
     * @param OrderInterface $order
     * @param int $customerId
     * @return void
     * @throws \Exception
     */
    private function guardQuoteBelongsToCurrentCustomer(OrderInterface $order, int $customerId)
    {
        $orderCustomerId = $order->getCustomerId();
        if ((int)$orderCustomerId !== $customerId) {
            $this->logger->debug("RESTORE-QUOTE-MUTATION::guardQuoteBelongsToCurrentCustomer - customer id '$customerId' doesn't match with order customer id '$orderCustomerId'");
            throw new \Exception(__('The current customer isn\'t authorized.'));
        }
    }

    /**
     * Check if the quote is still active, only quotes that are not active will be activated
     *
     * @param Quote $quote
     * @return void
     * @throws \Exception
     */
    private function guardQuoteIsStillActive(Quote $quote)
    {
        if ($quote->getIsActive()) {
            $this->logger->debug("RESTORE-QUOTE-MUTATION::guardQuoteIsStillActive - quote is still activated");

            throw new \Exception(__('The quote is still active.'));
        }
    }

    /**
     * Gets the customer id from the user context
     * @param ContextInterface $context
     * @param int|null $customerId
     * @return int|null
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function getCustomerIdFromContext(ContextInterface $context): mixed
    {
        $customerId = null;
        if ($context->getUserType() === UserContextInterface::USER_TYPE_GUEST) {
            return $customerId;
        }

        //only perform validations if the user is anonymous.
        if ($this->checkoutSession->getQuote()->getCustomerId() || !$this->customerSession->getCustomer()->getId()) {
            /** @var ContextInterface $context */
            if (false === $context->getExtensionAttributes()->getIsCustomer()) {
                throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
            }

            $customer = $this->getCustomer->execute($context);
            $customerId = $customer->getId();

            if (!empty($this->customerSession) && $customerId !== $this->customerSession->getCustomer()->getId()) {
                throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
            }
        }
        return $customerId;
    }

    /**
     * Restore a customer or guest's quote
     * @param Quote $quote
     * @param OrderInterface|null $order
     * @return array<mixed>
     */
    public function restoreQuote(string $cartIdMasked, Quote $quote, OrderInterface $order = null)
    {
        $quote->setIsActive(1)->setReservedOrderId(null);

        $this->cartRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
        $this->eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
        $this->logger->debug("RESTORE-QUOTE-MUTATION::restoreQuote - Quote with id $cartIdMasked was restored");

        return [
            'result' => 'OK',
            'cart_id' => $cartIdMasked
        ];
    }
}
