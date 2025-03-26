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

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use VRPayment\Payment\Model\Service\Order\TransactionService;

/**
 * Payment gateway command to accept a payment.
 */
class AcceptPaymentCommand implements CommandInterface
{

    /**
     *
     * @var TransactionService
     */
    private $orderTransactionService;

    /**
     *
     * @param TransactionService $orderTransactionService
     */
    public function __construct(TransactionService $orderTransactionService)
    {
        $this->orderTransactionService = $orderTransactionService;
    }

    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();

        $this->orderTransactionService->accept($payment->getOrder());
    }
}