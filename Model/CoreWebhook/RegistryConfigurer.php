<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook;

use Magento\Framework\ObjectManagerInterface;

use VRPayment\PluginCore\Webhook\WebhookProcessor;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerRegistry;
use VRPayment\PluginCore\Webhook\Enum\WebhookListener;

use VRPayment\Payment\Model\CoreWebhook\DeliveryIndication\ManualCheckRequiredListener;
use VRPayment\Payment\Model\CoreWebhook\ManualTask\UpdateListener as ManualTaskUpdateListener;
use VRPayment\Payment\Model\CoreWebhook\PaymentMethodConfiguration\SynchronizeListener;
use VRPayment\Payment\Model\CoreWebhook\Refund\FailedListener as RefundFailedListener;
use VRPayment\Payment\Model\CoreWebhook\Refund\SuccessfulListener as RefundSuccessfulListener;
use VRPayment\Payment\Model\CoreWebhook\Token\UpdateTokenListener;
use VRPayment\Payment\Model\CoreWebhook\TokenVersion\UpdateTokenVersionListener;
use VRPayment\Payment\Model\CoreWebhook\Transaction\AuthorizedListener;
use VRPayment\Payment\Model\CoreWebhook\Transaction\FailedListener;
use VRPayment\Payment\Model\CoreWebhook\Transaction\FulfillListener;
use VRPayment\Payment\Model\CoreWebhook\Transaction\VoidedListener;
use VRPayment\Payment\Model\CoreWebhook\TransactionCompletion\FailedListener
    as TransactionCompletionFailedListener;
use VRPayment\Payment\Model\CoreWebhook\TransactionInvoice\CaptureListener;

use VRPayment\PluginCore\DeliveryIndication\State as DeliveryIndicationState;
use VRPayment\PluginCore\ManualTask\State as ManualTaskState;
use VRPayment\PluginCore\PaymentMethod\State as PaymentMethodConfigurationState;
use VRPayment\PluginCore\Refund\State as RefundState;
use VRPayment\PluginCore\Token\State as TokenState;
use VRPayment\PluginCore\Token\Version\State as TokenVersionState;
use VRPayment\PluginCore\Transaction\State as TransactionState;
use VRPayment\PluginCore\Transaction\Completion\State as TransactionCompletionState;
use VRPayment\PluginCore\Transaction\Invoice\State as TransactionInvoiceState;

/**
 * Configures the WebhookListenerRegistry by adding all Magento listeners.
 */
class RegistryConfigurer
{

    /**
     *
     * @param ObjectManagerInterface $objectManager
     * @param WebhookProcessor $webhookProcessor
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly WebhookProcessor $webhookProcessor,
    ) {
    }

    /**
     * Adds all necessary listeners to the registry. Call this once before processing webhooks.
     *
     * @return void
     */
    public function configure(): void
    {
        // Get the registry instance directly from the processor
        $registry = $this->webhookProcessor->getListenerRegistry();

        $registry->addListener(
            WebhookListener::TRANSACTION,
            TransactionState::FAILED->value,
            $this->objectManager->create(FailedListener::class)
        );
        $registry->addListener(
            WebhookListener::TRANSACTION,
            TransactionState::AUTHORIZED->value,
            $this->objectManager->create(AuthorizedListener::class)
        );
        $registry->addListener(
            WebhookListener::TRANSACTION,
            TransactionState::FULFILL->value,
            $this->objectManager->create(FulfillListener::class)
        );
        $registry->addListener(
            WebhookListener::TRANSACTION,
            TransactionState::VOIDED->value,
            $this->objectManager->create(VoidedListener::class)
        );

        $registry->addListener(
            WebhookListener::TRANSACTION_COMPLETION,
            TransactionCompletionState::FAILED->value,
            $this->objectManager->create(TransactionCompletionFailedListener::class)
        );

        $registry->addListener(
            WebhookListener::TRANSACTION_INVOICE,
            TransactionInvoiceState::PAID->value,
            $this->objectManager->create(CaptureListener::class),
        );
        $registry->addListener(
            WebhookListener::TRANSACTION_INVOICE,
            TransactionInvoiceState::NOT_APPLICABLE->value,
            $this->objectManager->create(CaptureListener::class),
        );

        $registry->addListener(
            WebhookListener::REFUND,
            RefundState::FAILED->value,
            $this->objectManager->create(RefundFailedListener::class)
        );
        $registry->addListener(
            WebhookListener::REFUND,
            RefundState::SUCCESSFUL->value,
            $this->objectManager->create(RefundSuccessfulListener::class)
        );

        $registry->addListener(
            WebhookListener::DELIVERY_INDICATION,
            DeliveryIndicationState::MANUAL_CHECK_REQUIRED->value,
            $this->objectManager->create(ManualCheckRequiredListener::class)
        );

        $registry->addListener(
            WebhookListener::MANUAL_TASK,
            ManualTaskState::OPEN->value,
            $this->objectManager->create(ManualTaskUpdateListener::class)
        );
        $registry->addListener(
            WebhookListener::MANUAL_TASK,
            ManualTaskState::DONE->value,
            $this->objectManager->create(ManualTaskUpdateListener::class)
        );
        $registry->addListener(
            WebhookListener::MANUAL_TASK,
            ManualTaskState::EXPIRED->value,
            $this->objectManager->create(ManualTaskUpdateListener::class)
        );

        $registry->addListener(
            WebhookListener::PAYMENT_METHOD_CONFIGURATION,
            PaymentMethodConfigurationState::ACTIVE->value,
            $this->objectManager->create(SynchronizeListener::class)
        );
        $registry->addListener(
            WebhookListener::PAYMENT_METHOD_CONFIGURATION,
            PaymentMethodConfigurationState::INACTIVE->value,
            $this->objectManager->create(SynchronizeListener::class)
        );
        $registry->addListener(
            WebhookListener::PAYMENT_METHOD_CONFIGURATION,
            PaymentMethodConfigurationState::DELETING->value,
            $this->objectManager->create(SynchronizeListener::class)
        );
        $registry->addListener(
            WebhookListener::PAYMENT_METHOD_CONFIGURATION,
            PaymentMethodConfigurationState::DELETED->value,
            $this->objectManager->create(SynchronizeListener::class)
        );

        $registry->addListener(
            WebhookListener::TOKEN,
            TokenState::ACTIVE->value,
            $this->objectManager->create(UpdateTokenListener::class)
        );
        $registry->addListener(
            WebhookListener::TOKEN,
            TokenState::INACTIVE->value,
            $this->objectManager->create(UpdateTokenListener::class)
        );
        $registry->addListener(
            WebhookListener::TOKEN,
            TokenState::DELETING->value,
            $this->objectManager->create(UpdateTokenListener::class)
        );
        $registry->addListener(
            WebhookListener::TOKEN,
            TokenState::DELETED->value,
            $this->objectManager->create(UpdateTokenListener::class)
        );

        $registry->addListener(
            WebhookListener::TOKEN_VERSION,
            TokenVersionState::ACTIVE->value,
            $this->objectManager->create(UpdateTokenVersionListener::class)
        );
        $registry->addListener(
            WebhookListener::TOKEN_VERSION,
            TokenVersionState::OBSOLETE->value,
            $this->objectManager->create(UpdateTokenVersionListener::class)
        );
    }
}
