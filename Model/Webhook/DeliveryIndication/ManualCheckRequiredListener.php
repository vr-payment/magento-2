<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\Webhook\DeliveryIndication;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\PluginCore\DeliveryIndication\DeliveryIndicationGatewayInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;

class ManualCheckRequiredListener implements WebhookListenerInterface
{

    /**
     *
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DeliveryIndicationGatewayInterface $deliveryIndicationGateway
     * @param OrderResourceModel $orderResourceModel
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionInfoRepositoryInterface $transactionInfoRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly DeliveryIndicationGatewayInterface $deliveryIndicationGateway,
        private readonly OrderResourceModel $orderResourceModel,
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
        return new ManualCheckRequiredCommand(
            $context,
            $this->logger,
            $this->orderRepository,
            $this->transactionInfoRepository,
            $this->searchCriteriaBuilder,
            $this->deliveryIndicationGateway,
            $this->orderResourceModel,
        );
    }
}
