<?php
/**
 * VRPay Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPay (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Observer;

use Magento\Framework\DB\TransactionFactory as DBTransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use VRPayment\Payment\Api\TransactionInfoManagementInterface;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Service\Order\TransactionService;
use VRPayment\Sdk\Model\TransactionState;
use VRPayment\Sdk\Service\ChargeFlowService;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Observer to create an invoice and confirm the transaction when the quote is submitted.
 */
class SubmitQuote implements ObserverInterface
{

    /**
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @var DBTransactionFactory
     */
    private $dbTransactionFactory;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @var TransactionInfoManagementInterface
     */
    private $transactionInfoManagement;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param DBTransactionFactory $dbTransactionFactory
     * @param Helper $helper
     * @param TransactionService $transactionService
     * @param TransactionInfoManagementInterface $transactionInfoManagement
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param ApiClient $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(OrderRepositoryInterface $orderRepository, DBTransactionFactory $dbTransactionFactory,
        Helper $helper, TransactionService $transactionService,
        TransactionInfoManagementInterface $transactionInfoManagement,
        TransactionInfoRepositoryInterface $transactionInfoRepository, ApiClient $apiClient,  LoggerInterface $logger, CheckoutSession $checkoutSession)
    {
        $this->orderRepository = $orderRepository;
        $this->dbTransactionFactory = $dbTransactionFactory;
        $this->helper = $helper;
        $this->transactionService = $transactionService;
        $this->transactionInfoManagement = $transactionInfoManagement;
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getOrder();

        try{
            $this->logger->debug("SUBMIT-QUOTE-SERVICE::execute - Clear session");
            $this->checkoutSession->unsTransaction();
            $this->checkoutSession->unsPaymentMethods();
        } catch (LocalizedException $ignored){}

        $transactionId = $order->getVrpaymentTransactionId();
        if (! empty($transactionId)) {
            if (! $this->checkTransactionInfo($order)) {
                $this->cancelOrder($order);
                throw new LocalizedException(\__('vrpayment_checkout_failure'));
            }

            $transaction = $this->transactionService->getTransaction($order->getVrpaymentSpaceId(),
                $order->getVrpaymentTransactionId());
            $this->transactionInfoManagement->update($transaction, $order);

            $invoice = $this->createInvoice($order);

            $transaction = $this->transactionService->confirmTransaction($transaction, $order, $invoice,
                $this->helper->isAdminArea(), $order->getVrpaymentToken());
            $this->transactionInfoManagement->update($transaction, $order);
        }

        if ($order->getVrpaymentChargeFlow() && $this->helper->isAdminArea()) {
            $this->apiClient->getService(ChargeFlowService::class)->applyFlow(
                $order->getVrpaymentSpaceId(), $order->getVrpaymentTransactionId());

            if ($order->getVrpaymentToken() != null) {
                $this->transactionService->waitForTransactionState($order,
                    [
                        TransactionState::AUTHORIZED,
                        TransactionState::COMPLETED,
                        TransactionState::FULFILL
                    ], 3);
            }
        }
    }

    /**
     * Checks whether the transaction info for the transaction linked to the order is already linked to another order.
     *
     * @param Order $order
     * @return boolean
     */
    private function checkTransactionInfo(Order $order)
    {
        try {
            $info = $this->getTransactionInfo($order);

            if ($info === null) {
                return true;
            }

            //if the transaction was created by pwa behaviour, it's nothing to do
            if ($info->isExternalPaymentUrl()) {
                return true;
            }

            if ($info->getOrderId() != $order->getId()) {
                return false;
            }
        } catch (NoSuchEntityException $e) {}
        return true;
    }



    /**
     * Get the transaction info.
     *
     * @param Order $order
     * @return \VRPayment\Payment\Api\Data\TransactionInfoInterface|null
     */
    private function getTransactionInfo(Order $order)
    {
        try {
            return $this->transactionInfoRepository->getByTransactionId(
                $order->getVrpaymentSpaceId(),
                $order->getVrpaymentTransactionId()
            );
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Creates an invoice for the order.
     *
     * @param Order $order
     * @return Order\Invoice
     */
    private function createInvoice(Order $order)
    {
        $invoice = $order->prepareInvoice();
        $invoice->register();
        $invoice->setTransactionId(
            $order->getVrpaymentSpaceId() . '_' . $order->getVrpaymentTransactionId());

        $this->dbTransactionFactory->create()
            ->addObject($order)
            ->addObject($invoice)
            ->save();
        return $invoice;
    }

    /**
     * Cancels the given order and invoice linked to the transaction.
     *
     * @param Order $order
     * @return void
     */
    private function cancelOrder(Order $order)
    {
        $invoice = $this->getInvoiceForTransaction($order);
        if ($invoice) {
            $order->setVrpaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);
        }
        $order->registerCancellation(null, false);
        $this->orderRepository->save($order);
    }

    /**
     * Gets the invoice linked to the given transaction.
     *
     * @param Order $order
     * @return Invoice
     */
    private function getInvoiceForTransaction(Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Invoice $invoice */
            if (\strpos($invoice->getTransactionId() ?? '',
                $order->getVrpaymentSpaceId() . '_' . $order->getVrpaymentTransactionId()) ===
                0 && $invoice->getState() != Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }
    }

}
