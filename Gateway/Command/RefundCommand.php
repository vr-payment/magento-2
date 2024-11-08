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
namespace VRPayment\Payment\Gateway\Command;

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
     * @param LoggerInterface $logger
     * @param LocaleHelper $localeHelper
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param RefundService $refundService
     * @param ApiClient $apiClient
     */
    public function __construct(LoggerInterface $logger, LocaleHelper $localeHelper,
        RefundJobRepositoryInterface $refundJobRepository, RefundService $refundService, ApiClient $apiClient)
    {
        $this->logger = $logger;
        $this->localeHelper = $localeHelper;
        $this->refundJobRepository = $refundJobRepository;
        $this->refundService = $refundService;
        $this->apiClient = $apiClient;
    }

    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();
        $creditmemo = $payment->getCreditmemo();

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
            } elseif ($refund->getState() == RefundState::PENDING || $refund->getState() == RefundState::MANUAL_CHECK) {
                $creditmemo->setVrpaymentKeepRefundJob(true);
                throw new \Magento\Framework\Exception\LocalizedException(
                    \__('The refund was requested successfully, but is still pending on the gateway.'));
            }

            $creditmemo->setVrpaymentExternalId($refund->getExternalId());
            $this->refundJobRepository->delete($refundJob);
        }
    }
}