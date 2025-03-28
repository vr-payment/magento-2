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

use Magento\Framework\Math\Random;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use VRPayment\Payment\Api\TokenInfoRepositoryInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Sdk\Model\Token;

/**
 * Payment gateway command to initialize a payment.
 */
class InitializeCommand implements CommandInterface
{

    /**
     *
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     *
     * @var Random
     */
    private $random;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var TokenInfoRepositoryInterface
     */
    private $tokenInfoRepository;

    /**
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param Random $random
     * @param Helper $helper
     * @param TokenInfoRepositoryInterface $tokenInfoRepository
     */
    public function __construct(CartRepositoryInterface $quoteRepository, Random $random, Helper $helper,
        TokenInfoRepositoryInterface $tokenInfoRepository)
    {
        $this->quoteRepository = $quoteRepository;
        $this->random = $random;
        $this->helper = $helper;
        $this->tokenInfoRepository = $tokenInfoRepository;
    }

    /**
     * An invoice is created and the transaction updated to match the order and confirmed.
     * The order state is set to {@link Order::STATE_PENDING_PAYMENT}.
     *
     * @see CommandInterface::execute()
     */
    public function execute(array $commandSubject)
    {
        $stateObject = SubjectReader::readStateObject($commandSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        $order->setCanSendNewEmailFlag(false);
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($order->getQuoteId());

        if (! $quote->getVrpaymentSpaceId() || ! $quote->getVrpaymentTransactionId()) {
            throw new \InvalidArgumentException('The VR Payment payment transaction is not set on the quote.');
        }

        if ($order->getVrpaymentSpaceId() != null ||
            $order->getVrpaymentTransactionId() != null) {
            throw new \InvalidArgumentException(
                'The VR Payment payment transaction has already been set on the order.');
        }

        $order->setVrpaymentSpaceId($quote->getVrpaymentSpaceId());
        $order->setVrpaymentTransactionId($quote->getVrpaymentTransactionId());
        $order->setVrpaymentSecurityToken($this->random->getUniqueHash());

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        if ($this->helper->isAdminArea()) {
            // Tell the order to apply the charge flow after it is saved.
            $order->setVrpaymentChargeFlow(true);
            $order->setVrpaymentToken($this->getToken($quote));
        }
    }

    /**
     * @param Quote $quote
     * @return void|Token
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getToken(Quote $quote)
    {
        if ($this->helper->isAdminArea()) {
            $tokenInfoId = $quote->getPayment()->getData('vrpayment_token');
            if ($tokenInfoId) {
                $tokenInfo = $this->tokenInfoRepository->get($tokenInfoId);
                $token = new Token();
                $token->setId($tokenInfo->getTokenId());
                return $token;
            }
        }
    }
}