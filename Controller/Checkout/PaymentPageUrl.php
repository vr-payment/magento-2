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
namespace VRPayment\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use VRPayment\Payment\Model\Service\Order\TransactionService;
use Magento\Store\Model\ScopeInterface;

/**
 * Frontend controller action to handle payment page url.
 */
class PaymentPageUrl extends \VRPayment\Payment\Controller\Checkout
{

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param TransactionService $transactionService
     */
    public function __construct(Context $context, CheckoutSession $checkoutSession, 
    ScopeConfigInterface $scopeConfig, TransactionService $transactionService)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->transactionService = $transactionService;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order) {
            $this->messageManager->addErrorMessage(__('No order found. Please try again.'));
            return $redirect->setPath('checkout/cart');
        }

        try {
            $integrationMethod = $this->scopeConfig->getValue('vrpayment_payment/checkout/integration_method', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $url = $this->transactionService->getTransactionPaymentUrl($order, $integrationMethod);
            $configurationId = $order->getPayment()
                ->getMethodInstance()
                ->getPaymentMethodConfiguration()
                ->getConfigurationId();
            return $redirect->setPath($url . '&paymentMethodConfigurationId=' . (string)$configurationId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while trying to redirect to payment page. Please try again.'));
            return $redirect->setPath('checkout/cart');
        }
    }
}