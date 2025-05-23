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
namespace VRPayment\Payment\Model\Service\Quote;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use VRPayment\Payment\Api\PaymentMethodConfigurationManagementInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\CustomerIdManipulationException;
use VRPayment\Payment\Model\Service\AbstractTransactionService;
use VRPayment\Sdk\ApiException;
use VRPayment\Sdk\VersioningException;
use VRPayment\Sdk\Model\AbstractTransactionPending;
use VRPayment\Sdk\Model\AddressCreate;
use VRPayment\Sdk\Model\CustomersPresence;
use VRPayment\Sdk\Model\Transaction;
use VRPayment\Sdk\Model\TransactionCreate;
use VRPayment\Sdk\Model\TransactionPending;
use VRPayment\Sdk\Model\TransactionState;
use VRPayment\Sdk\Service\TransactionIframeService;
use VRPayment\Sdk\Service\TransactionLightboxService;
use VRPayment\Sdk\Service\TransactionPaymentPageService;
use VRPayment\Sdk\Service\TransactionService as TransactionApiService;
use Psr\Log\LoggerInterface;

/**
 * Service to handle transactions in quote context.
 */
class TransactionService extends AbstractTransactionService
{
    /**
     * Number of attempts to call the portal API
     */
    const NUMBER_OF_ATTEMPTS = 3;

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
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @var LineItemService
     */
    private $lineItemService;

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @var boolean
     */
    private $submittingOrder = false;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRegistry $customerRegistry
     * @param PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement
     * @param ApiClient $apiClient
     * @param CookieManagerInterface $cookieManager
     * @param LineItemService $lineItemService
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(ResourceConnection $resource,Helper $helper, ScopeConfigInterface $scopeConfig, CustomerRegistry $customerRegistry,
    ApiClient $apiClient,PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement,CookieManagerInterface $cookieManager,
    LineItemService $lineItemService, CheckoutSession $checkoutSession, LoggerInterface $logger)
    {
        parent::__construct($resource, $customerRegistry, $paymentMethodConfigurationManagement,
        $apiClient, $cookieManager);
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->apiClient = $apiClient;
        $this->lineItemService = $lineItemService;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

     /**
     * Gets the payment URL in the session if exists.
     *
     * @param Quote $quote
     * @return string|null
     */
    private function getPaymentUrlInSession(Quote $quote)
    {  
        $url = $this->checkoutSession->getPaymentUrl();
        $transactionId = $quote->getVrpaymentTransactionId();
        if ($url && preg_match('/transactionId=(\d+)/', $url, $matches)
            && isset($matches[1]) && $matches[1] == $transactionId) {           
            return $url;
        }
    }

    /**
     * Gets the URL to the JavaScript library that is required to display the iframe payment form.
     *
     * @param Quote $quote
     * @return string
     */
    public function getJavaScriptUrl(Quote $quote)
    {
        $url = $this->getPaymentUrlInSession($quote);
        if ($url !== null) {            
            $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getJavaScriptUrl URL already exists: ".$url);
            return $url;
        }
        
        $transaction = $this->getTransactionByQuote($quote);
        $url = $this->apiClient->getService(TransactionIframeService::class)->javascriptUrl(
            $transaction->getLinkedSpaceId(), $transaction->getId());
        $this->checkoutSession->setPaymentUrl($url);
        $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getJavaScriptUrl new URL: ".$url);
        return $url;
    }

    /**
     * Gets the URL to the JavaScript library that is required to display the lightbox payment form.
     *
     * @param Quote $quote
     * @return string
     */
    public function getLightboxUrl(Quote $quote)
    {
        $url = $this->getPaymentUrlInSession($quote);
        if ($url !== null) {          
            $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getLightboxUrl API CALL URL already exists: ".$url);
            return $url;
        }

        $transaction = $this->getTransactionByQuote($quote);
        $url = $this->apiClient->getService(TransactionLightboxService::class)->javascriptUrl(
            $transaction->getLinkedSpaceId(), $transaction->getId());
        $this->checkoutSession->setPaymentUrl($url);
        $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getLightboxUrl new API CALL URL: ".$url);
        return $url;
    }

    /**
     * Gets the URL to the payment page.
     *
     * @param Quote $quote
     * @return string
     */
    public function getPaymentPageUrl(Quote $quote)
    {
        $url = $this->getPaymentUrlInSession($quote);
        if ($url !== null) {           
            $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getPaymentPageUrl URL already exists: ".$url);
            return $url;
        }
        $transaction = $this->getTransactionByQuote($quote);
        $url = $this->apiClient->getService(TransactionPaymentPageService::class)->paymentPageUrl(
            $transaction->getLinkedSpaceId(), $transaction->getId());
        $this->checkoutSession->setPaymentUrl($url);
        $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getPaymentPageUrl new URL: ".$url);
        return $url;
    }

    /**
     * Gets the payment methods that can be used with the given quote.
     *
     * @param Quote $quote
     * @return \VRPayment\Sdk\Model\PaymentMethodConfiguration
     * @throws ApiException
     * @throws VersioningException
     * @throws \VRPayment\Payment\Model\ApiClientException
     * @throws \VRPayment\Sdk\Http\ConnectionException
     */
    public function getPossiblePaymentMethods(Quote $quote)
    {
        $gdprEnabled = $this->scopeConfig->getValue(
            'vrpayment_payment/gdpr/gdpr_enabled',
            ScopeInterface::SCOPE_STORE, $quote->getStoreId()
        );

        if ($gdprEnabled != 'enabled') {
            $this->updateTransactionByQuote($quote);
        }

        $transaction = $this->getTransactionByQuote($quote);
        $integrationMethod = $this->scopeConfig->getValue(
            'vrpayment_payment/checkout/integration_method',
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );
        try {
            $paymentMethods = $this->apiClient->getApiClient()->getTransactionService()->fetchPaymentMethods(
                $transaction->getLinkedSpaceId(),
                $transaction->getId(),
                $integrationMethod
            );
        } catch (ApiException $e) {
            $paymentMethodsArray[$quote->getId()] = null;
            try {
                $this->checkoutSession->setPaymentMethods($paymentMethodsArray);
            } catch (LocalizedException $ignored) {
            }
            throw $e;
        }
        $this->updatePaymentMethodConfigurations($paymentMethods);
        return $paymentMethods;
    }

    /**
     * Gets whether the payment method is available for the given quote.
     *
     * @param Quote $quote
     * @param int $paymentMethodConfigurationId
     * @param int $paymentMethodConfigurationSpaceId
     * @return boolean
     */
    public function isPaymentMethodAvailable(Quote $quote, $paymentMethodConfigurationId, $paymentMethodConfigurationSpaceId)
    {
        $possiblePaymentMethods = $this->getPossiblePaymentMethods($quote);
        foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
            if ($possiblePaymentMethod->getId() == $paymentMethodConfigurationId
                && $possiblePaymentMethod->getSpaceId() == $paymentMethodConfigurationSpaceId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the transaction for the given quote.
     *
     * If there is not transaction for the quote, a new one is created.
     *
     * @param Quote $quote
     * @return Transaction
     */
    public function getTransactionByQuote(Quote $quote)
    {
        //The transaction id can be null if the quote is restored when the payment process fails.
        //This ensures that the cache has an available transaction. 
        $transactionId = $quote->getVrpaymentTransactionId();
        if (empty($transactionId)) {
            $transactionArray[$quote->getId()] = $this->createTransactionByQuote($quote);
        }

        $transactionArray = $this->getTransactionArrayFromSession();
        if (! \array_key_exists($quote->getId(), $transactionArray) ||
            $transactionArray[$quote->getId()] == null)
        {
            $transactionId = $quote->getVrpaymentTransactionId();
            if (empty($transactionId)) {
                $transactionArray[$quote->getId()] = $this->createTransactionByQuote($quote);
            } else {
                $transactionArray[$quote->getId()] = $this->updateTransactionByQuote($quote);
            }
            try{
                $this->checkoutSession->setTransaction($transactionArray);
            } catch (LocalizedException $ignored){}
        }
        return $transactionArray[$quote->getId()];
    }

    /**
     * Check if the cached transaction is still available or not on the portal.
     *
     * @param Quote $quote
     * @return bool
     */
    public function checkTransactionIsStillAvailable(Quote $quote)
    {
        $transactionArray = $this->getTransactionArrayFromSession();
        $transaction = null;
        if (isset($transactionArray[$quote->getId()]) && $transactionArray[$quote->getId()] !== null) {
            $transactionInSession = $transactionArray[$quote->getId()];

            //If the status of the transaction in cache is PENDING,
            //you should check its status in the portal, it may be closed, failed or declined.
            if ($transactionInSession->getState() == TransactionState::PENDING) {
                $transaction = $this->apiClient->getService(TransactionApiService::class)->read(
                    $quote->getVrpaymentSpaceId(),
                    $quote->getVrpaymentTransactionId()
                );
            }

            $states = [
                TransactionState::DECLINE,
                TransactionState::FAILED
            ];
            if ($transaction instanceof Transaction && in_array($transaction->getState(), $states)) {
                return false;
            }
            //here we make sure that both portal and session transactions are the same.
            if ($transaction->getId() !== $transactionInSession->getId()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Creates a transaction for the given quote.
     *
     * @param Quote $quote
     * @return Transaction
     */
    private function createTransactionByQuote(Quote $quote)
    {
        $spaceId = $this->scopeConfig->getValue('vrpayment_payment/general/space_id',
            ScopeInterface::SCOPE_STORE, $quote->getStoreId());

        $createTransaction = new TransactionCreate();
        $createTransaction->setCustomersPresence(CustomersPresence::VIRTUAL_PRESENT);
        $createTransaction->setAutoConfirmationEnabled(false);
        $createTransaction->setChargeRetryEnabled(false);
        $this->assembleTransactionDataFromQuote($createTransaction, $quote);
        $transaction = $this->apiClient->getApiClient()->getTransactionService()->create($spaceId, $createTransaction);
        $this->updateQuote($quote, $transaction);
        //here the order must be updated with the space and transaction, this avoids error before landing on the payment page
        $quote->setVrpaymentTransactionId($transaction->getId());
        $quote->setVrpaymentSpaceId($spaceId);
        $quote->save();
        return $transaction;
    }

    /**
     * Updates the transaction with the given quote's data.
     *
     * @param Quote $quote
     * @throws VersioningException
     * @return Transaction
     */
    private function updateTransactionByQuote(Quote $quote)
    {
        for ($i = 0; $i < self::NUMBER_OF_ATTEMPTS; $i ++) {
            try {
                $spaceId = $this->scopeConfig->getValue('vrpayment_payment/general/space_id',
                    ScopeInterface::SCOPE_STORE, $quote->getStoreId());
                if ($quote->getVrpaymentSpaceId() != $spaceId) {
                    return $this->createTransactionByQuote($quote);
                }

                $transaction = $this->apiClient->getService(TransactionApiService::class)->read(
                    $quote->getVrpaymentSpaceId(), $quote->getVrpaymentTransactionId());

                if (!($transaction instanceof Transaction) || $transaction->getState() != TransactionState::PENDING) {
                    return $this->createTransactionByQuote($quote);
                }

                if (! empty($transaction->getCustomerId()) && $transaction->getCustomerId() != $quote->getCustomerId()) {
                    if ($this->submittingOrder) {
                        throw new CustomerIdManipulationException();
                    } else {
                        return $this->createTransactionByQuote($quote);
                    }
                }

                return $this->createTransactionPending($quote, $transaction);
            } catch (VersioningException $e) {
                // Try to update the transaction again, if a versioning exception occurred.
            }
        }
        throw new VersioningException(__FUNCTION__);
    }

    /**
     * @param Quote $quote
     * @param Transaction $transaction
     * @return mixed
     */
    private function createTransactionPending(Quote $quote, $transaction)
    {
        $pendingTransaction = new TransactionPending();
        $pendingTransaction->setId($transaction->getId());
        $pendingTransaction->setVersion($transaction->getVersion());
        $this->assembleTransactionDataFromQuote($pendingTransaction, $quote);
        return $this->apiClient->getService(TransactionApiService::class)->update(
            $quote->getVrpaymentSpaceId(), $pendingTransaction);
        }

    /**
     * Assembles the transaction data from the given quote.
     *
     * @param AbstractTransactionPending $transaction
     * @param Quote $quote
     * @return void
     */
    private function assembleTransactionDataFromQuote(AbstractTransactionPending $transaction, Quote $quote)
    {
        $quote->collectTotals();
        $transaction->setAllowedPaymentMethodConfigurations([]);
        $transaction->setCurrency($quote->getQuoteCurrencyCode());
        $transaction->setBillingAddress($this->convertQuoteBillingAddress($quote));
        $transaction->setShippingAddress($this->convertQuoteShippingAddress($quote));
        $transaction->setCustomerEmailAddress(
            $this->getCustomerEmailAddress($quote->getCustomerEmail(), $quote->getCustomerId()));
        $transaction->setLanguage(
            $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $quote->getStoreId()));
        $transaction->setLineItems($this->lineItemService->convertQuoteLineItems($quote));
        if (! empty($quote->getCustomerId())) {
            $transaction->setCustomerId($quote->getCustomerId());
        }
        if ($quote->getShippingAddress()) {
            $transaction->setShippingMethod(
                $this->helper->fixLength(
                    $this->helper->getFirstLine($quote->getShippingAddress()
                        ->getShippingDescription()), 200));
        }

        if ($transaction instanceof TransactionCreate) {
            $transaction->setSpaceViewId(
                $this->scopeConfig->getValue('vrpayment_payment/general/space_view_id',
                    ScopeInterface::SCOPE_STORE, $quote->getStoreId()));
            $transaction->setDeviceSessionIdentifier($this->getDeviceSessionIdentifier());
        }
    }

    protected function getCustomerEmailAddress($customerEmailAddress, $customerId)
    {
        $emailAddress = parent::getCustomerEmailAddress($customerEmailAddress, $customerId);
        if (! empty($emailAddress)) {
            return $emailAddress;
        } else {
            return $this->checkoutSession->getVRPaymentCheckoutEmailAddress();
        }
    }

    /**
     * Converts the billing address of the given quote.
     *
     * @param Quote $quote
     * @return \VRPayment\Sdk\Model\AddressCreate
     */
    private function convertQuoteBillingAddress(Quote $quote)
    {
        if (! $quote->getBillingAddress()) {
            return null;
        }
        $address = $this->convertAddress($quote->getBillingAddress());

        $gdprEnabled = $this->scopeConfig->getValue('vrpayment_payment/gdpr/gdpr_enabled',
            ScopeInterface::SCOPE_STORE, $quote->getStoreId());

        if ($gdprEnabled == 'enabled') {
            // removing GDPR sensitive information
            $address->setDateOfBirth('');
            $address->setFamilyName('');
            $address->setGivenName('');
            $address->setStreet('');
        }
        $address->setDateOfBirth($this->getDateOfBirth($quote->getCustomerDob(), $quote->getCustomerId()));
        $address->setEmailAddress($this->getCustomerEmailAddress($quote->getCustomerEmail(), $quote->getCustomerId()));
        $address->setGender($this->getGender($quote->getCustomerGender(), $quote->getCustomerId()));
        $address->setSalesTaxNumber($this->getTaxNumber($quote->getCustomerTaxvat(), $quote->getCustomerId()));
        return $address;
    }

    /**
     * Converts the shipping address of the given quote.
     *
     * @param Quote $quote
     * @return \VRPayment\Sdk\Model\AddressCreate
     */
    private function convertQuoteShippingAddress(Quote $quote)
    {
        if (! $quote->getShippingAddress()) {
            return null;
        }
        $address = $this->convertAddress($quote->getShippingAddress());
        $gdprEnabled = $this->scopeConfig->getValue('vrpayment_payment/gdpr/gdpr_enabled',
            ScopeInterface::SCOPE_STORE, $quote->getStoreId());

        if ($gdprEnabled == 'enabled') {
            // removing GDPR sensitive information
            $address->setDateOfBirth('');
            $address->setFamilyName('');
            $address->setGivenName('');
            $address->setStreet('');
        }
        $address->setEmailAddress($this->getCustomerEmailAddress($quote->getCustomerEmail(), $quote->getCustomerId()));
        return $address;
    }

    /**
     * Converts the given address.
     *
     * @param Address $customerAddress
     * @return AddressCreate
     */
    private function convertAddress(Address $customerAddress)
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
        $address->setStreet($this->helper->fixLength($customerAddress->getStreetFull(), 300));
        return $address;
    }

    /**
     * @return void
     */
    public function setSubmittingOrder()
    {
        $this->submittingOrder = true;
    }

    /**
     * Get the array of payment methods from the session
     * If it doesn't exist, an empty one will be initialized
     * @return array
     */
    private function getPaymentMethodsArrayFromSession()
    {
        $paymentMethodsArray = [];
        try {
            if ($this->checkoutSession->getPaymentMethods()) {
                $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getPaymentMethodsArrayFromSession - Array already set");
                $paymentMethodsArray = $this->checkoutSession->getPaymentMethods();
                //TODO CHECK IF CURRENCY AND LANGUAGE IS THE SAME, OTHERWISE REFRESH THE PAYMENT METHODS
            } else {
                $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getPaymentMethodsArrayFromSession - Array NOT set");
            }
        } catch (LocalizedException $ignored) {
            $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getPaymentMethodsArrayFromSession - Array NOT set (exception)");
        }
        return $paymentMethodsArray;
    }

    /**
     * Get the array of transaction from the session
     * If it doesn't exist, an empty one will be initialized
     * @return array
     */
    private function getTransactionArrayFromSession()
    {
        $transactionArray = [];
        try {

            if ($this->checkoutSession->getTransaction()) {
                $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getTransactionArrayFromSession - Array already set");
                $transactionArray = $this->checkoutSession->getTransaction();
            } else {
                $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getTransactionArrayFromSession - Array NOT set");
            }
        } catch (LocalizedException $ignored) {
            $this->logger->debug("QUOTE-TRANSACTION-SERVICE::getTransactionArrayFromSession - Array NOT set (exception)");
        }
        return $transactionArray;
    }
}
