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

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Transaction\Completion\TransactionCompletionService;
use VRPayment\PluginCore\Transaction\Exception\TransactionException;
use VRPayment\PluginCore\Transaction\Void\State as CoreVoidState;

/**
 * Payment gateway command to void a payment.
 */
class VoidCommand implements CommandInterface
{

    /**
     *
     * @param TransactionCompletionService $completionService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TransactionCompletionService $completionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Void the order transaction for the given payment command.
     *
     * @param array $commandSubject
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();
        $order = $payment->getOrder();

        try {
            $void = $this->completionService->void(
                (int) $order->getVrpaymentSpaceId(),
                (int) $order->getVrpaymentTransactionId()
            );
        } catch (TransactionException $e) {
            $this->logger->error('Void failed on the gateway.', [
                'orderId' => $order->getIncrementId(),
                'exception' => $e,
            ]);
            throw new LocalizedException(
                \__('The void of the payment failed on the gateway: %1', $e->getMessage())
            );
        }

        if ($void->state === CoreVoidState::FAILED) {
            $this->logger->error('Void was rejected by the gateway.', [
                'orderId' => $order->getIncrementId(),
            ]);
            throw new \Magento\Framework\Exception\LocalizedException(
                \__('The void of the payment failed on the gateway.')
            );
        }

        $this->logger->info('Void completed.', ['orderId' => $order->getIncrementId()]);
    }
}
