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
namespace VRPayment\Payment\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;
use VRPayment\PluginCore\Refund\Exception\InvalidRefundException;
use VRPayment\PluginCore\Refund\Exception\RefundException;
use VRPayment\PluginCore\Refund\RefundService;
use VRPayment\PluginCore\Refund\State as CoreState;
use VRPayment\PluginCore\Transaction\Exception\TransactionException;

/**
 * Backend controller action to send a refund request to VR Payment.
 */
class Refund extends \VRPayment\Payment\Controller\Adminhtml\Order
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_creditmemo';

    /**
     *
     * @var ForwardFactory
     */
    private $resultForwardFactory;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param RefundService $refundService
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        RefundJobRepositoryInterface $refundJobRepository,
        RefundService $refundService,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->refundJobRepository = $refundJobRepository;
        $this->refundService = $refundService;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Process refund request for the given order and redirect back to order view.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $isIgnorePendingRefundStatusEnabled = $this->scopeConfig->getValue(
            'vrpayment_payment/pending_refund_status/pending_refund_status_enabled'
        );
        if ($orderId) {
            try {
                $refundJob = $this->refundJobRepository->getByOrderId($orderId);

                try {
                    $refund = $this->refundService->createRefund(
                        (int) $refundJob->getSpaceId(),
                        $refundJob->getRefund()
                    );

                    if ($refund->state == CoreState::FAILED) {
                        $this->messageManager->addErrorMessage(
                            $refund->failureReason?->getDefault()
                            ?? \__('The refund could not be processed on the gateway.')
                        );
                    } elseif (! $isIgnorePendingRefundStatusEnabled &&
                        ( $refund->state == CoreState::PENDING ||
                        $refund->state == CoreState::MANUAL_CHECK )) {
                        $this->messageManager->addErrorMessage(
                            \__('The refund was requested successfully, but is still pending on the gateway.')
                        );
                    } else {
                        $this->messageManager->addSuccessMessage(\__('Successfully refunded.'));
                    }
                } catch (InvalidRefundException|RefundException|TransactionException $e) {
                    $this->messageManager->addErrorMessage(\__($e->getLocalizedMessage()->getDefault()));
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        \__('There has been an error while sending the refund to the gateway.')
                    );
                }
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(\__('For this order no refund request exists.'));
            }
            return $this->resultRedirectFactory->create()->setPath('sales/order/view', [
                'order_id' => $orderId
            ]);
        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }
}
