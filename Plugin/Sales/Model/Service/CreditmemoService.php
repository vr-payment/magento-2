<?php
/**
 * VR payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Plugin\Sales\Model\Service;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\RefundJobFactory;
use VRPayment\Payment\Model\Payment\Method\Adapter as PaymentMethodAdapter;
use VRPayment\Payment\Model\Service\LineItemReductionService;
use VRPayment\Payment\Model\Service\RefundService;
use VRPayment\Sdk\Service\RefundService as ApiRefundService;

/**
 * Interceptor to handle refund jobs when a refund is triggered.
 */
class CreditmemoService
{

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var LineItemReductionService
     */
    private $lineItemReductionService;

    /**
     *
     * @var RefundJobFactory
     */
    private $refundJobFactory;

    /**
     *
     * @var RefundJobRepositoryInterface
     */
    private $refundJobRepository;

    /**
     *
     * @var RefundService
     */
    private $refundService;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param LoggerInterface $logger
     * @param LineItemReductionService $lineItemReductionService
     * @param RefundJobFactory $refundJobFactory
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param RefundService $refundService
     * @param ApiClient $apiClient
     */
    public function __construct(LoggerInterface $logger, LineItemReductionService $lineItemReductionService,
        RefundJobFactory $refundJobFactory, RefundJobRepositoryInterface $refundJobRepository, RefundService $refundService, ApiClient $apiClient)
    {
        $this->logger = $logger;
        $this->lineItemReductionService = $lineItemReductionService;
        $this->refundJobFactory = $refundJobFactory;
        $this->refundJobRepository = $refundJobRepository;
        $this->refundService = $refundService;
        $this->apiClient = $apiClient;
    }

    /**
     * @param \Magento\Sales\Model\Service\CreditmemoService $subject
     * @param callable $proceed
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return mixed
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function aroundRefund(\Magento\Sales\Model\Service\CreditmemoService $subject, callable $proceed,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo, $offlineRequested = false)
    {
        try {
            return $proceed($creditmemo, $offlineRequested);
        } catch (\Exception $e) {
            if ($creditmemo->getVrpaymentKeepRefundJob() !== true) {
                try {
                    $this->refundJobRepository->delete(
                        $this->refundJobRepository->getByOrderId($creditmemo->getOrderId()));
                } catch (NoSuchEntityException $exc) {}
            }
            throw $e;
        }
    }

    /**
     * @param \Magento\Sales\Model\Service\CreditmemoService $subject
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeRefund(\Magento\Sales\Model\Service\CreditmemoService $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo, $offlineRequested = false)
    {
        if ($offlineRequested || ! $creditmemo->getInvoice()) {
            return null;
        }

        if ($creditmemo->getOrder()
            ->getPayment()
            ->getMethodInstance() instanceof PaymentMethodAdapter &&
            $creditmemo->getVrpaymentExternalId() == null) {
            try {
                $this->handleExistingRefundJob($creditmemo->getOrder());

                $refundCreate = $this->refundService->createRefund($creditmemo);
                $this->refundService->createRefundJob($creditmemo->getInvoice(), $refundCreate);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(\__($e->getMessage()));
            }
        }
    }

    /**
     * Checks if there is an existing refund job for the given order and trys to send to refund to the gateway again.
     *
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    private function handleExistingRefundJob(Order $order)
    {
        try {
            $existingRefundJob = $this->refundJobRepository->getByOrderId($order->getId());
            try {
                $this->apiClient->getService(ApiRefundService::class)->refund(
                    $order->getVrpaymentSpaceId(), $existingRefundJob->getRefund());
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            throw new \Magento\Framework\Exception\LocalizedException(
                \__('As long as there is an open creditmemo for the order, no new creditmemo can be created.'));
        } catch (NoSuchEntityException $e) {}
    }


}