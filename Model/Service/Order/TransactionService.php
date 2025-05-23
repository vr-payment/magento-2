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
namespace VRPayment\Payment\Model\Service\Order;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\DataObject;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\PaymentMethodConfigurationManagementInterface;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Helper\LineItem as LineItemHelper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Config\Source\IntegrationMethod;
use VRPayment\Payment\Model\CustomerIdManipulationException;
use VRPayment\Payment\Model\Service\AbstractTransactionService;
use VRPayment\Sdk\VersioningException;
use VRPayment\Sdk\Model\AbstractTransactionPending;
use VRPayment\Sdk\Model\AddressCreate;
use VRPayment\Sdk\Model\CriteriaOperator;
use VRPayment\Sdk\Model\EntityQuery;
use VRPayment\Sdk\Model\EntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType;
use VRPayment\Sdk\Model\Token;
use VRPayment\Sdk\Model\Transaction;
use VRPayment\Sdk\Model\TransactionCreate;
use VRPayment\Sdk\Model\TransactionInvoiceState;
use VRPayment\Sdk\Model\TransactionPending;
use VRPayment\Sdk\Model\TransactionState;
use VRPayment\Sdk\Service\DeliveryIndicationService;
use VRPayment\Sdk\Service\TransactionCompletionService;
use VRPayment\Sdk\Service\TransactionInvoiceService;
use VRPayment\Sdk\Service\TransactionIframeService;
use VRPayment\Sdk\Service\TransactionLightboxService;
use VRPayment\Sdk\Service\TransactionPaymentPageService;
use VRPayment\Sdk\Service\TransactionService as TransactionApiService;
use VRPayment\Sdk\Service\TransactionVoidService;

/**
 * Service to handle transactions in order context.
 */
class TransactionService extends AbstractTransactionService
{
    /**
     * Number of attempts to call the portal API
     */
    const NUMBER_OF_ATTEMPTS = 3;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @var LineItemService
     */
    private $lineItemService;

    /**
     *
     * @var LineItemHelper
     */
    private $lineItemHelper;

    /**
     *
     * @var TransactionInfoRepositoryInterface
     */
    private $transactionInfoRepository;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param CustomerRegistry $customerRegistry
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement
     * @param ApiClient $apiClient
     * @param CookieManagerInterface $cookieManager
     * @param LoggerInterface $logger
     * @param LineItemService $lineItemService
     * @param LineItemHelper $lineItemHelper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     */
    public function __construct(ResourceConnection $resource, Helper $helper, ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager, CustomerRegistry $customerRegistry, OrderRepositoryInterface $orderRepository,
        PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement, ApiClient $apiClient,
        CookieManagerInterface $cookieManager, LoggerInterface $logger, LineItemService $lineItemService,
        LineItemHelper $lineItemHelper, TransactionInfoRepositoryInterface $transactionInfoRepository)
    {
        parent::__construct($resource, $customerRegistry, $paymentMethodConfigurationManagement, $apiClient,
        $cookieManager);
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->lineItemService = $lineItemService;
        $this->lineItemHelper = $lineItemHelper;
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->apiClient = $apiClient;
    }

    /**
     * Updates the transaction with the given order's data and confirms it.
     *
     * @param Transaction $transaction
     * @param Order $order
     * @param Invoice $invoice
     * @param boolean $chargeFlow
     * @param Token $token
     * @throws VersioningException
     * @return Transaction
     */
    public function confirmTransaction(Transaction $transaction, Order $order, Invoice $invoice, $chargeFlow = false,
        Token $token = null)
    {
        if ($transaction->getState() == TransactionState::CONFIRMED) {
            return $transaction;
        } elseif ($transaction->getState() != TransactionState::PENDING) {
            $this->cancelOrder($order, $invoice);
            throw new LocalizedException(\__('vrpayment_checkout_failure'));
        }

        $spaceId = $order->getVrpaymentSpaceId();
        $transactionId = $order->getVrpaymentTransactionId();

        for ($i = 0; $i < self::NUMBER_OF_ATTEMPTS; $i ++) {
            try {
                if ($i > 0) {
                    $transaction = $this->getTransaction($spaceId, $transactionId);
                    if ($transaction instanceof Transaction && $transaction->getState() == TransactionState::CONFIRMED) {
                        return $transaction;
                    } elseif (! ($transaction instanceof Transaction) ||
                        $transaction->getState() != TransactionState::PENDING) {
                        $this->cancelOrder($order, $invoice);
                        throw new LocalizedException(\__('vrpayment_checkout_failure'));
                    }
                }

                if (! empty($transaction->getCustomerId()) && $transaction->getCustomerId() != $order->getCustomerId()) {
                    throw new CustomerIdManipulationException();
                }

                $pendingTransaction = new TransactionPending();
                $pendingTransaction->setId($transaction->getId());
                $pendingTransaction->setVersion($transaction->getVersion());
                $this->assembleTransactionDataFromOrder($pendingTransaction, $order, $invoice, $chargeFlow, $token);
                return $this->apiClient->getService(TransactionApiService::class)->confirm($spaceId, $pendingTransaction);
            } catch (VersioningException $e) {
                // Try to update the transaction again, if a versioning exception occurred.
            }
        }
        throw new VersioningException(__FUNCTION__);
    }

    /**
     * Cancels the given order and invoice linked to the transaction.
     *
     * @param Order $order
     * @param Invoice $invoice
     * @return void
     */
    private function cancelOrder(Order $order, Invoice $invoice)
    {
        if ($invoice) {
            $order->setVrpaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);
        }
        $order->registerCancellation(null, false);
        $this->orderRepository->save($order);
    }

    /**
     * Assembles the transaction data from the given order and invoice.
     *
     * @param AbstractTransactionPending $transaction
     * @param Order $order
     * @param Invoice $invoice
     * @param boolean $chargeFlow
     * @param Token $token
     * @return void
     */
    protected function assembleTransactionDataFromOrder(AbstractTransactionPending $transaction, Order $order,
        Invoice $invoice, $chargeFlow = false, Token $token = null)
    {
        $transaction->setCurrency($order->getOrderCurrencyCode());
        $transaction->setBillingAddress($this->convertOrderBillingAddress($order));
        $transaction->setShippingAddress($this->convertOrderShippingAddress($order));
        $transaction->setCustomerEmailAddress(
            $this->getCustomerEmailAddress($order->getCustomerEmail(), $order->getCustomerId()));
        $transaction->setLanguage(
            $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $order->getStoreId()));
        $transaction->setLineItems($this->lineItemService->convertOrderLineItems($order));
        $this->logAdjustmentLineItemInfo($order, $transaction);
        $transaction->setMerchantReference($order->getIncrementId());
        $transaction->setInvoiceMerchantReference($invoice->getIncrementId());
        if (! empty($order->getCustomerId())) {
            $transaction->setCustomerId($order->getCustomerId());
        }
        if ($order->getShippingAddress()) {
            $transaction->setShippingMethod(
                $this->helper->fixLength(
                    $this->helper->getFirstLine($order->getShippingAddress()
                        ->getShippingDescription()), 200));
        }
        if ($transaction instanceof TransactionCreate) {
            $transaction->setSpaceViewId(
                $this->scopeConfig->getValue('vrpayment_payment/general/space_view_id',
                    ScopeInterface::SCOPE_STORE, $order->getStoreId()));
            $transaction->setDeviceSessionIdentifier($this->getDeviceSessionIdentifier());
        }
        if ($chargeFlow) {
            $transaction->setAllowedPaymentMethodConfigurations(
                [
                    $order->getPayment()
                        ->getMethodInstance()
                        ->getPaymentMethodConfiguration()
                        ->getConfigurationId()
                ]);
        } else {
            //default behaviour
            $successUrl = $this->buildUrl('vrpayment_payment/transaction/success', $order);
            $failureUrl = $this->buildUrl('vrpayment_payment/transaction/failure', $order);

            try {
                $transactionInfo = $this->transactionInfoRepository->getByTransactionId(
                    $order->getVrpaymentSpaceId(),
                    $order->getVrpaymentTransactionId()
                );

                //external return url to the shop, such as pwa
                if ($transactionInfo !== null && $transactionInfo->isExternalPaymentUrl()) {
                    $successUrl = $this->buildUrl($transactionInfo->getSuccessUrl(), $order, true);
                    $failureUrl = $this->buildUrl($transactionInfo->getFailureUrl(), $order, true);

                    //force a particular payment method
                    $transaction->setAllowedPaymentMethodConfigurations(
                        [
                            $order->getPayment()
                            ->getMethodInstance()
                            ->getPaymentMethodConfiguration()
                            ->getConfigurationId()
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->debug("ORDER-TRANSACTION-SERVICE::assembleTransactionDataFromOrder error: " . $e->getMessage());
            }

            $this->logger->debug("ORDER-TRANSACTION-SERVICE::assembleTransactionDataFromOrder url: " . $successUrl . '?utm_nooverride=1');
            $this->logger->debug("ORDER-TRANSACTION-SERVICE::assembleTransactionDataFromOrder url: " . $failureUrl . '?utm_nooverride=1');
            $transaction->setSuccessUrl(sprintf('%s?utm_nooverride=1', $successUrl));
            $transaction->setFailedUrl(sprintf('%s?utm_nooverride=1', $failureUrl));
        }
        if ($token != null) {
            $transaction->setToken($token->getId());
        }
        $metaData = $this->collectMetaData($order);
        if (! empty($metaData) && is_array($metaData)) {
            $transaction->setMetaData($metaData);
        }
    }

    /**
     * Checks whether an adjustment line item has been added to the transaction and adds a log message if so.
     *
     * @param Order $order
     * @param TransactionPending $transaction
     * @return void
     */
    protected function logAdjustmentLineItemInfo(Order $order, TransactionPending $transaction)
    {
        foreach ($transaction->getLineItems() as $lineItem) {
            if ($lineItem->getUniqueId() == 'adjustment') {
                $expectedSum = $this->lineItemHelper->getTotalAmountIncludingTax($transaction->getLineItems()) -
                    $lineItem->getAmountIncludingTax();
                $this->logger->warning(
                    'An adjustment line item has been added to the transaction ' . $transaction->getId() .
                    ', because the line item total amount of ' .
                    $this->helper->roundAmount($order->getGrandTotal(), $order->getOrderCurrencyCode()) .
                    ' did not match the invoice amount of ' . $expectedSum . ' of the order ' . $order->getId() . '.');
                return;
            }
        }
    }

    /**
     * @param Order $order
     * @return array<mixed>|mixed|null
     */
    protected function collectMetaData(Order $order)
    {
        $transport = new DataObject([
            'metaData' => []
        ]);
        $this->eventManager->dispatch('vrpayment_payment_collect_meta_data',
            [
                'transport' => $transport,
                'order' => $order
            ]);
        return $transport->getData('metaData');
    }

    /**
     * Builds the URL to an endpoint that is aware of the given order.
     *
     * @param string $route
     * @param Order $order
     * @param bool $extarnalUrl
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function buildUrl($route, Order $order, $extarnalUrl = false)
    {
        $token = $order->getVrpaymentSecurityToken();
        if (empty($token)) {
            throw new LocalizedException(
                \__('The VR Payment security token needs to be set on the order to build the URL.'));
        }

        if ($extarnalUrl) {
            return sprintf('%s/order/%d/token/%s/', $route, $order->getId(), $token);
        }

        return $order->getStore()->getUrl($route,
            [
                '_secure' => true,
                'order_id' => $order->getId(),
                'token' => $token
            ]);
    }

    /**
     * Gets the payment url of the transaction according to the type of integration.
     *
     * @param Order $order
     * @param string $integrationType
     * @return string
     */
    public function getTransactionPaymentUrl(Order $order, string $integrationType)
    {
        $transaction = $this->getTransaction(
            $order->getVrpaymentSpaceId(),
            $order->getVrpaymentTransactionId()
        );

        switch ($integrationType) {
            case IntegrationMethod::IFRAME:
                $serviceClass = TransactionIframeService::class;
                break;
            case IntegrationMethod::LIGHTBOX:
                $serviceClass = TransactionLightboxService::class;
                break;
            case IntegrationMethod::PAYMENT_PAGE:
                $serviceClass = TransactionPaymentPageService::class;
                break;
            default:
                $serviceClass = TransactionPaymentPageService::class;
            }

            $url = $this->apiClient->getService($serviceClass)->paymentPageUrl(
            $transaction->getLinkedSpaceId(),
            $transaction->getId()
        );

        $this->logger->debug("ORDER-TRANSACTION-SERVICE::getTransactionPaymentUrl URL: " . $url);
        return $url;
    }

    /**
     * Converts the billing address of the given order.
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\AddressCreate
     */
    protected function convertOrderBillingAddress(Order $order)
    {
        if (! $order->getBillingAddress()) {
            return null;
        }

        $address = $this->convertAddress($order->getBillingAddress());
        $address->setDateOfBirth($this->getDateOfBirth($order->getCustomerDob(), $order->getCustomerId()));
        $address->setEmailAddress($this->getCustomerEmailAddress($order->getCustomerEmail(), $order->getCustomerId()));
        $address->setGender($this->getGender($order->getCustomerGender(), $order->getCustomerId()));
        return $address;
    }

    /**
     * Converts the shipping address of the given order.
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\AddressCreate
     */
    protected function convertOrderShippingAddress(Order $order)
    {
        if (! $order->getShippingAddress()) {
            return null;
        }

        $address = $this->convertAddress($order->getShippingAddress());
        $address->setEmailAddress($this->getCustomerEmailAddress($order->getCustomerEmail(), $order->getCustomerId()));
        return $address;
    }

    /**
     * Converts the given address.
     *
     * @param Address $customerAddress
     * @return AddressCreate
     */
    protected function convertAddress(Address $customerAddress)
    {
        $address = new AddressCreate();
        $address->setSalutation(
            $this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getPrefix()), 20));
        $address->setCity($this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getCity()), 100));
        $address->setCountry($customerAddress->getCountryId());
        $address->setFamilyName(
            $this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getLastname()), 100));
        $address->setGivenName(
            $this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getFirstname()), 100));
        $address->setOrganizationName(
            $this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getCompany()), 100));
        $address->setPhoneNumber($customerAddress->getTelephone());
        if (! empty($customerAddress->getCountryId()) && ! empty($customerAddress->getRegionCode())) {
            $address->setPostalState($customerAddress->getCountryId() . '-' . $customerAddress->getRegionCode());
        }
        $address->setPostCode(
            $this->helper->fixLength($this->helper->removeLinebreaks($customerAddress->getPostcode()), 40));
        $street = $customerAddress->getStreet();
        $address->setStreet($this->helper->fixLength(\is_array($street) ? \implode("\n", $street) : $street, 300));
        return $address;
    }

    /**
     * Completes the transaction linked to the given order.
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\TransactionCompletion
     */
    public function complete(Order $order)
    {
        return $this->apiClient->getService(TransactionCompletionService::class)->completeOnline(
            $order->getVrpaymentSpaceId(), $order->getVrpaymentTransactionId());
    }

    /**
     * Voids the transaction linked to the given order.
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\TransactionVoid
     */
    public function void(Order $order)
    {
        return $this->apiClient->getService(TransactionVoidService::class)->voidOnline(
            $order->getVrpaymentSpaceId(), $order->getVrpaymentTransactionId());
    }

    /**
     * Marks the delivery indication belonging to the given payment as suitable.
     * Note: there are no delivery indication for Authorized transactions
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\DeliveryIndication
     */
    public function accept(Order $order)
    {
        return $this->apiClient->getService(DeliveryIndicationService::class)->markAsSuitable(
            $order->getVrpaymentSpaceId(), $this->getDeliveryIndication($order)
                ->getId());
    }

    /**
     * Marks the delivery indication belonging to the given payment as not suitable.
     * Note: there are no delivery indication for Authorized transactions
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\DeliveryIndication
     */
    public function deny(Order $order)
    {
        return $this->apiClient->getService(DeliveryIndicationService::class)->markAsNotSuitable(
            $order->getVrpaymentSpaceId(), $this->getDeliveryIndication($order)
                ->getId());
    }

    /**
     *
     * @param Order $order
     * @return \VRPayment\Sdk\Model\DeliveryIndication
     */
    protected function getDeliveryIndication(Order $order)
    {
        $query = new EntityQuery();
        $query->setFilter(
            $this->helper->createEntityFilter('transaction.id', $order->getVrpaymentTransactionId()));
        $query->setNumberOfEntities(1);
        return \current(
            $this->apiClient->getService(DeliveryIndicationService::class)->search(
                $order->getVrpaymentSpaceId(), $query));
    }

    /**
     * Gets the transaction invoice linked to the given order.
     *
     * @param Order $order
     * @throws \Exception
     * @return \VRPayment\Sdk\Model\TransactionInvoice
     */
    public function getTransactionInvoice(Order $order)
    {
        $query = new EntityQuery();
        $filter = new EntityQueryFilter();
        $filter->setType(EntityQueryFilterType::_AND);
        $filter->setChildren(
            [
                $this->helper->createEntityFilter('state', TransactionInvoiceState::CANCELED,
                    CriteriaOperator::NOT_EQUALS),
                $this->helper->createEntityFilter('completion.lineItemVersion.transaction.id',
                    $order->getVrpaymentTransactionId())
            ]);
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $result = $this->apiClient->getService(TransactionInvoiceService::class)->search(
            $order->getVrpaymentSpaceId(), $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            throw new NoSuchEntityException();
        }
    }

    /**
     * Waits for the transaction to be in one of the given states.
     *
     * @param Order $order
     * @param array $states
     * @param int $maxWaitTime
     * @return boolean
     */
    public function waitForTransactionState(Order $order, array $states, $maxWaitTime = 10)
    {
        $startTime = \microtime(true);
        while (true) {
            if (\microtime(true) - $startTime >= $maxWaitTime) {
                return false;
            }

            $transactionInfo = $this->transactionInfoRepository->getByOrderId($order->getId());
            if (\in_array($transactionInfo->getState(), $states)) {
                return true;
            }

            \sleep(2);
        }
    }
}