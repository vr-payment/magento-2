<?php
/**
 * VRPayment Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPayment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Webhook\Listener\Transaction;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Webhook listener command to handle completed transactions.
 */
class CompletedCommand extends AbstractCommand
{
    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var AuthorizedCommand
     */
    private $authorizedCommand;

    /**
     *
     * @param LoggerInterface $logger
     * @param AuthorizedCommand $authorizedCommand
     */
    public function __construct(AuthorizedCommand $authorizedCommand, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->authorizedCommand = $authorizedCommand;
    }

    /**
     *
     * @param \VRPayment\Sdk\Model\Transaction $entity
     * @param Order $order
     * @return void
     */
    public function execute($entity, Order $order)
    {
        $this->logger->debug("CompletedCommand::execute state");
        $this->authorizedCommand->execute($entity, $order);
    }
}