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
namespace VRPayment\Payment\Block\Adminhtml\Sales\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Exception\NoSuchEntityException;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;

/**
 * Block to inform about a pending refund on the backend order view.
 */
class View extends Template
{

    /**
     *
     * @var Registry
     */
    private $coreRegistry;

    /**
     *
     * @var RefundJobRepositoryInterface
     */
    private $refundJobRepository;

    /**
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param array $data
     */
    public function __construct(Context $context, Registry $coreRegistry,
        RefundJobRepositoryInterface $refundJobRepository, array $data = [])
    {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->refundJobRepository = $refundJobRepository;
    }

    /**
     * Gets whether there is a pending refund for the order.
     *
     * @return boolean
     */
    public function hasPendingRefund()
    {
        try {
            $this->refundJobRepository->getByOrderId($this->getOrder()
                ->getId());
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Gets the URL to send the refund request to the gateway.
     *
     * @return string
     */
    public function getRefundUrl()
    {
        return $this->getUrl('vrpayment_payment/order/refund', [
            'order_id' => $this->getOrder()
                ->getId()
        ]);
    }

    /**
     * Gets the order model object.
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('sales_order');
    }
}