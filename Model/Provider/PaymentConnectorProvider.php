<?php
/**
 * VRPay Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPay (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Provider;

use Magento\Framework\Cache\FrontendInterface;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Sdk\Service\PaymentConnectorService;

/**
 * Provider of payment connector information from the gateway.
 */
class PaymentConnectorProvider extends AbstractProvider
{

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param FrontendInterface $cache
     * @param ApiClient $apiClient
     */
    public function __construct(FrontendInterface $cache, ApiClient $apiClient)
    {
        parent::__construct($cache, 'vrpayment_payment_connectors',
            \VRPayment\Sdk\Model\PaymentConnector::class);
        $this->apiClient = $apiClient;
    }

    /**
     * Gets the payment connector by the given id.
     *
     * @param string $id
     * @return \VRPayment\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Gets a list of payment connectors.
     *
     * @return \VRPayment\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    /**
     * @return mixed
     */
    protected function fetchData()
    {
        return $this->apiClient->getService(PaymentConnectorService::class)->all();
    }

    protected function getId($entry)
    {
        /** @var \VRPayment\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}