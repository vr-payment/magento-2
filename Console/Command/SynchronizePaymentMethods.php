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
namespace VRPayment\Payment\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VRPayment\Payment\Api\PaymentMethodConfigurationManagementInterface;

/**
 * Command to synchronize the payment methods.
 */
class SynchronizePaymentMethods extends Command
{

    /**
     *
     * @var PaymentMethodConfigurationManagementInterface
     */
    private $paymentMethodConfigurationManagement;

    /**
     *
     * @param PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement
     */
    public function __construct(
        PaymentMethodConfigurationManagementInterface $paymentMethodConfigurationManagement)
    {
        parent::__construct();
        $this->paymentMethodConfigurationManagement = $paymentMethodConfigurationManagement;
    }

    protected function configure()
    {
        $this->setName('vrpayment:payment-method:synchronize')->setDescription(
            'Synchronizes the VRPayment payment methods.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->paymentMethodConfigurationManagement->synchronize($output);
    }
}