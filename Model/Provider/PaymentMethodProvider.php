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
namespace VRPayment\Payment\Model\Provider;

use Magento\Framework\Cache\FrontendInterface;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Sdk\Service\PaymentMethodService;

/**
 * Provider of payment method information from the gateway.
 */
class PaymentMethodProvider extends AbstractProvider
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
        parent::__construct($cache, 'vrpayment_payment_methods',
            \VRPayment\Sdk\Model\PaymentMethod::class);
        $this->apiClient = $apiClient;
    }

    /**
     * Gets the payment method by the given id.
     *
     * @param string $id
     * @return \VRPayment\Sdk\Model\PaymentMethod
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Gets a list of payment methods.
     *
     * @return \VRPayment\Sdk\Model\PaymentMethod[]
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
        return $this->apiClient->getService(PaymentMethodService::class)->all();
    }

    /**
     * @param mixed $entry
     * @return int
     */
    protected function getId($entry)
    {
        /** @var \VRPayment\Sdk\Model\PaymentMethod $entry */
        return $entry->getId();
    }
}