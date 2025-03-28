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

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
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
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Model\Service\Quote\TransactionService;
use VRPayment\Payment\Api\TransactionInfoManagementInterface;

class UpdateTransactionUrls implements ResolverInterface
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
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteIdService;

    /**
     *
     * @var TransactionService
     */
    private $transactionQuoteService;

    /**
     *
     * @var TransactionInfoManagementInterface
     */
    private $transactionInfoManagement;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(Session $customerSession, CheckoutSession $checkoutSession, GetCustomer $getCustomer,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteIdService, CartRepositoryInterface $cartRepository,
        TransactionService $transactionQuoteService, TransactionInfoManagementInterface $transactionInfoManagement, LoggerInterface $logger)
        {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->getCustomer = $getCustomer;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteIdService = $maskedQuoteIdToQuoteIdService;
        $this->logger = $logger;
        $this->transactionQuoteService = $transactionQuoteService;
        $this->transactionInfoManagement = $transactionInfoManagement;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        //only perform validations if the user is anonymous.
        if ($this->checkoutSession->getQuote()->getCustomerId()) {
            /** @var ContextInterface $context */
            if (false === $context->getExtensionAttributes()->getIsCustomer()) {
                throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
            }

            $customer = $this->getCustomer->execute($context);
            if ($this->customerSession === null && $customer->getId() !== $this->customerSession->getCustomer()->getId()) {
                throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
            }
        }

        try {
            $successUrl = $args['input']['success_url'];
            $failureUrl = $args['input']['failure_url'];
            $cartIdMasked = $args['input']['cart_id'];
            return $this->setTransactionUrls($cartIdMasked, $successUrl, $failureUrl);
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

    /**
     * Update transaction urls to redirect the customer after placing the order
     *
     * @param string $cartIdMasked
     * @param string $successUrl
     * @param string $failureUrl
     * @return array<mixed>
     * @throws LocalizedException
     */
    private function setTransactionUrls($cartIdMasked, $successUrl, $failureUrl)
    {
        try {
            // Convert the masked ID to the real quote ID
            $quoteId = $this->maskedQuoteIdToQuoteIdService->execute($cartIdMasked);

            // Get the quote using the actual ID
            /** @var Quote $quote */
            $quote = $this->cartRepository->get($quoteId);
            $spaceId = $quote->getVrpaymentSpaceId();
            $transactionId = $quote->getVrpaymentTransactionId();

            //At this step, if the transaction ID and space ID are empty,
            //it could be because the enableAvailablePaymentMethodsCheck option is active,
            //and the quote no longer has these values.
            if (empty($spaceId) || empty($transactionId)) {
                //Fetching the JavaScript URL here allows updating the quote
                //with the current transaction and saving it in the session.
                $this->transactionQuoteService->getJavaScriptUrl($quote);

                //values from the session
                $quote = $this->checkoutSession->getQuote();
                $spaceId = $quote->getVrpaymentSpaceId();
                $transactionId = $quote->getVrpaymentTransactionId();
            }

            //$quoteSession = $this->checkoutSession->getQuote();
            /** @var \VRPayment\Payment\Model\ResourceModel\TransactionInfo $transactionInfo */
            $transactionInfo = $this->transactionQuoteService->getTransaction(
                $quote->getVrpaymentSpaceId(),
                $quote->getVrpaymentTransactionId()
            );

            // Gets the ID reserved for the order from the quotation
            $orderId = $quote->getReservedOrderId();

            // Checks if the quote does not have an ID reserved for the order
            // If the quote id is used as an identifier, this will cause the urls to have to be reset
            // to the correct order id, otherwise the redirect to the correct transaction will not be possible.
            // This means that the user still has a quote and has not paid, the quote only becomes a quote once payment
            // has been made, so there is no order id at this point.
            // The transaction info id is set, so that there are no other unknown side effects, it's mandatory.
            if (!$orderId && !$quote->hasReservedOrderId()) {
                $orderId = $transactionInfo->getId();
            }

            $this->transactionInfoManagement->setRedirectUrls($transactionInfo, $orderId, $successUrl, $failureUrl);
            return ['result' => 'OK'];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return ['result' => 'KO. ' . $e->getMessage()];
        }
    }
}
