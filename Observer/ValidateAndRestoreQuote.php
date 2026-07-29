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
namespace VRPayment\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session as CheckoutSession;
use VRPayment\PluginCore\Transaction\TransactionGatewayInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer to validate and control quote restoration.
 */
class ValidateAndRestoreQuote implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var TransactionGatewayInterface
     */
    private $transactionGateway;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param CheckoutSession $checkoutSession
     * @param TransactionGatewayInterface $transactionGateway
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        TransactionGatewayInterface $transactionGateway,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->transactionGateway = $transactionGateway;
        $this->logger = $logger;
    }

    /**
     * Validate and restore the quote.
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        // After placeOrder, the session's current quote is a new empty one.
        // The quote we need to reactivate is referenced by the last real order.
        $order = $this->checkoutSession->getLastRealOrder();

        // Idempotent: if there's no last order (e.g. restoreQuote already ran
        // earlier in this request and unset lastRealOrderId), there's nothing
        // to do — silently return instead of throwing.
        if (!$order || !$order->getId()) {
            return;
        }

        // Block restore only when the VR Payment transaction is in a
        // terminal paid state. Magento's order state is unreliable here: the
        // order can sit in `processing` immediately after placeOrder while
        // the VR Payment transaction is still CONFIRMED/PROCESSING (e.g.,
        // the customer is on the 3DS page).
        $spaceId = $order->getVrpaymentSpaceId();
        $transactionId = $order->getVrpaymentTransactionId();
        if ($spaceId && $transactionId) {
            try {
                $transaction = $this->transactionGateway->find((int) $spaceId, (int) $transactionId);
                if ($transaction !== null && $transaction->state->isPaidLike()) {
                    throw new LocalizedException(
                        __('Your cart has already been paid for and cannot be restored.')
                    );
                }
            } catch (LocalizedException $e) {
                throw $e;
            } catch (\Exception $e) {
                // If the VR Payment API is unreachable, fail open and let
                // the restore proceed — better than stranding the customer.
                $this->logger->debug(
                    "Failed to retrieve the transaction:  " . $e->getMessage()
                );
            }
        }

        // Reactivates the quote and dispatches `restore_quote`. The abandoned
        // order stays in `pending_payment` until the VR Payment webhook
        // reports FAILED/DECLINED and FailedCommand/DeclineCommand cancels it.
        $this->checkoutSession->restoreQuote();
    }
}
