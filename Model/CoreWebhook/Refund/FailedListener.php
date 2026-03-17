<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\Refund;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Helper\Locale as LocaleHelper;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;

class FailedListener implements WebhookListenerInterface
{
    /**
     *
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param LocaleHelper $localeHelper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SdkProvider $sdkProvider
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LocaleHelper $localeHelper,
        private readonly TransactionInfoRepositoryInterface $transactionInfoRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SdkProvider $sdkProvider,
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
        return new FailedCommand(
            $context,
            $this->logger,
            $this->orderRepository,
            $this->localeHelper,
            $this->transactionInfoRepository,
            $this->searchCriteriaBuilder,
            $this->sdkProvider
        );
    }
}
