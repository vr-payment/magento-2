<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\Refund;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Helper\Locale as LocaleHelper;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Webhook\Command\WebhookCommand;
use VRPayment\PluginCore\Webhook\WebhookContext;
use VRPayment\Sdk\Model\Refund;

class FailedCommand extends WebhookCommand
{
    use RefundCommandTrait;

    /**
     *
     * @param WebhookContext $context
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param LocaleHelper $localeHelper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SdkProvider $sdkProvider
     */
    public function __construct(
        WebhookContext $context,
        LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LocaleHelper $localeHelper,
        private readonly TransactionInfoRepositoryInterface $transactionInfoRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SdkProvider $sdkProvider,
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * Execute failed command for the current webhook context.
     *
     * @return mixed
     */
    public function execute(): mixed
    {
        $this->logger->info(
            sprintf(
                'Running FailedCommand for entity ID: %d',
                $this->context->entityId
            )
        );

        $refund = $this->loadRefund();
        if (!$refund) {
            $this->logger->warning(
                sprintf(
                    'FailedCommand: No refund found for entity ID: %d',
                    $this->context->entityId
                )
            );
            return null;
        }

        $order = $this->findOrderFromRefund($refund);
        if (!$order) {
            $this->logger->warning(
                sprintf(
                    'FailedCommand: No order found for entity ID: %d',
                    $this->context->entityId
                )
            );
            return null;
        }

        $failureReason = $refund->getFailureReason();
        if ($failureReason) {
            $order->addCommentToStatusHistory(
                \__(
                    'The refund of %1 failed on the gateway: %2',
                    $order->getBaseCurrency()->formatTxt($refund->getAmount()),
                    $this->localeHelper->translate($failureReason->getDescription())
                )->render()
            );
            $this->orderRepository->save($order);
        }

        $this->logger->debug(sprintf('Command Failed for entity Refund/%d completed.', $this->context->entityId));
        // Return the objects needed by the postProcess hook
        return ['refund' => $refund, 'order' => $order];
    }
}
