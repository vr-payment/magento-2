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
use VRPayment\Sdk\Service\CurrencyService;

/**
 * Provider of currency information from the gateway.
 */
class CurrencyProvider extends AbstractProvider
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
        parent::__construct(
            $cache,
            'vrpayment_payment_currencies',
            \VRPayment\Sdk\Model\RestCurrency::class
        );
        $this->apiClient = $apiClient;
    }

    /**
     * Fetch currencies from the API.
     *
     * @return mixed
     */
    protected function fetchData()
    {
        return $this->apiClient->getService(CurrencyService::class)->all();
    }

    /**
     * Get currency ID from the given entry.
     *
     * @param \VRPayment\Sdk\Model\RestCurrency $entry
     * @return int
     */
    protected function getId($entry)
    {
        /** @var \VRPayment\Sdk\Model\RestCurrency $entry */
        return $entry->getCurrencyCode();
    }
}
