<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\Webhook\Refund;

use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Api\RefundJobRepositoryInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\Service\Order\TransactionService;
use VRPayment\PluginCore\Refund\RefundGatewayInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;

class SuccessfulListener implements WebhookListenerInterface
{

    /**
     *
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param TransactionService $transactionService
     * @param Helper $helper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RefundJobRepositoryInterface $refundJobRepository
     * @param RefundGatewayInterface $pluginCoreRefundGateway
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoRepositoryInterface $creditmemoRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoManagementInterface $creditmemoManagement,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly TransactionService $transactionService,
        private readonly Helper $helper,
        private readonly TransactionInfoRepositoryInterface $transactionInfoRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RefundJobRepositoryInterface $refundJobRepository,
        private readonly RefundGatewayInterface $pluginCoreRefundGateway
    ) {
    }

    /**
     * Create webhook command for the given context.
     *
     * @param WebhookContext $context
     * @return WebhookCommandInterface
     */
    public function getCommand(WebhookContext $context): WebhookCommandInterface
    {
        return new SuccessfulCommand(
            $context,
            $this->logger,
            $this->creditmemoRepository,
            $this->creditmemoFactory,
            $this->creditmemoManagement,
            $this->invoiceRepository,
            $this->stockConfiguration,
            $this->transactionService,
            $this->helper,
            $this->orderRepository,
            $this->transactionInfoRepository,
            $this->searchCriteriaBuilder,
            $this->refundJobRepository,
            $this->pluginCoreRefundGateway
        );
    }
}
