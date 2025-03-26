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
namespace VRPayment\Payment\Gateway\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;
use VRPayment\Payment\Helper\Locale as LocaleHelper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Service\RefundService;
use VRPayment\Sdk\Model\RefundState;
use VRPayment\Sdk\Service\RefundService as ApiRefundService;

/**
 * Payment gateway command to refund a payment.
 */
class RefundCommand implements CommandInterface
{

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var LocaleHelper
     */
    private $localeHelper;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @param LoggerInterface $logger
     * @param LocaleHelper $localeHelper
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param RefundService $refundService
     * @param ApiClient $apiClient
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        LocaleHelper $localeHelper,
        RefundJobRepositoryInterface $refundJobRepository,
        RefundService $refundService,
        ApiClient $apiClient,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->localeHelper = $localeHelper;
        $this->refundJobRepository = $refundJobRepository;
        $this->refundService = $refundService;
        $this->apiClient = $apiClient;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();
        $creditmemo = $payment->getCreditmemo();
        $isIgnorePendingRefundStatusEnabled = $this->scopeConfig->getValue('vrpayment_payment/pending_refund_status/pending_refund_status_enabled');

        if ($creditmemo->getVrpaymentExternalId() == null) {
            try {
                $refundJob = $this->refundJobRepository->getByOrderId($payment->getOrder()
                    ->getId());
            } catch (NoSuchEntityException $e) {
                $refund = $this->refundService->createRefund($creditmemo);
                $refundJob = $this->refundService->createRefundJob($creditmemo->getInvoice(), $refund);
            }

            try {
                $refund = $this->apiClient->getService(ApiRefundService::class)->refund(
                    $creditmemo->getOrder()
                        ->getVrpaymentSpaceId(), $refundJob->getRefund());
            } catch (\VRPayment\Sdk\ApiException $e) {
                if ($e->getResponseObject() instanceof \VRPayment\Sdk\Model\ClientError) {
                    $this->refundJobRepository->delete($refundJob);
                    throw new \Magento\Framework\Exception\LocalizedException(
                        \__($e->getResponseObject()->getMessage()));
                } else {
                    $creditmemo->setVrpaymentKeepRefundJob(true);
                    $this->logger->critical($e);
                    throw new \Magento\Framework\Exception\LocalizedException(
                        \__('There has been an error while sending the refund to the gateway.'));
                }
            } catch (\Exception $e) {
                $creditmemo->setVrpaymentKeepRefundJob(true);
                $this->logger->critical($e);
                throw new \Magento\Framework\Exception\LocalizedException(
                    \__('There has been an error while sending the refund to the gateway.'));
            }

            if ($refund->getState() == RefundState::FAILED) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    \__($this->localeHelper->translate($refund->getFailureReason()
                        ->getDescription())));
            } elseif ( ! $isIgnorePendingRefundStatusEnabled &&
                ( $refund->getState() == RefundState::PENDING ||
                $refund->getState() == RefundState::MANUAL_CHECK ) ) {
                $creditmemo->setVrpaymentKeepRefundJob(true);
                throw new \Magento\Framework\Exception\LocalizedException(
                    \__('The refund was requested successfully, but is still pending on the gateway.'));
            }

            $creditmemo->setVrpaymentExternalId($refund->getExternalId());
            $this->refundJobRepository->delete($refundJob);
        }
    }
}