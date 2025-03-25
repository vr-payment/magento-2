<?php
/**
 * VRPayment Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPayment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Service;


use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;
use VRPayment\Payment\Api\Data\RefundJobInterface;
use VRPayment\Payment\Model\RefundJobFactory;
use VRPayment\Sdk\Model\RefundCreate;
use VRPayment\Sdk\Model\RefundType;

/**
 * Service to handle creditmemos.
 */
class RefundService
{

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
     * @param LineItemReductionService $lineItemReductionService
     * @param RefundJobFactory $refundJobFactory
     * @param RefundJobRepositoryInterface $refundJobRepository
     */
    public function __construct(LineItemReductionService $lineItemReductionService,
        RefundJobFactory $refundJobFactory, RefundJobRepositoryInterface $refundJobRepository)
    {
        $this->lineItemReductionService = $lineItemReductionService;
        $this->refundJobFactory = $refundJobFactory;
        $this->refundJobRepository = $refundJobRepository;
    }

    /**
     * Creates a new refund job for the given invoice and refund.
     *
     * @param Invoice $invoice
     * @param RefundCreate $refund
     * @return \VRPayment\Payment\Model\RefundJob
     */
    public function createRefundJob(Invoice $invoice, RefundCreate $refund)
    {
        $entity = $this->refundJobFactory->create();
        $entity->setData(RefundJobInterface::ORDER_ID, $invoice->getOrderId());
        $entity->setData(RefundJobInterface::INVOICE_ID, $invoice->getId());
        $entity->setData(RefundJobInterface::SPACE_ID, $invoice->getOrder()
            ->getVrpaymentSpaceId());
        $entity->setData(RefundJobInterface::EXTERNAL_ID, $refund->getExternalId());
        $entity->setData(RefundJobInterface::REFUND, $refund);
        return $this->refundJobRepository->save($entity);
    }

    /**
     * Creates a refund creation model for the given creditmemo.
     *
     * @param Creditmemo $creditmemo
     * @return RefundCreate
     */
    public function createRefund(Creditmemo $creditmemo)
    {
        $refund = new RefundCreate();
        $refund->setExternalId(\uniqid($creditmemo->getOrderId() . '-'));

        try {
            $reductions = $this->lineItemReductionService->convertCreditmemo($creditmemo);
            $refund->setReductions($reductions);
        } catch (LineItemReductionException $e) {
            $refund->setAmount($creditmemo->getGrandTotal());
        }

        $refund->setTransaction($creditmemo->getOrder()
            ->getVrpaymentTransactionId());
        $refund->setType(RefundType::MERCHANT_INITIATED_ONLINE);
        return $refund;
    }

}