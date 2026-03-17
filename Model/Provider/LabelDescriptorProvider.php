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
use VRPayment\Sdk\Service\LabelDescriptionService;

/**
 * Provider of label descriptor information from the gateway.
 */
class LabelDescriptorProvider extends AbstractProvider
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
            'vrpayment_payment_label_descriptors',
            \VRPayment\Sdk\Model\LabelDescriptor::class
        );
        $this->apiClient = $apiClient;
    }

    /**
     * Fetch label descriptor ID from the API.
     *
     * @return mixed
     */
    protected function fetchData()
    {
        return $this->apiClient->getService(LabelDescriptionService::class)->all();
    }

    /**
     * Get label descriptor ID from the given entry.
     *
     * @param \VRPayment\Sdk\Model\LabelDescriptor $entry
     * @return int
     */
    protected function getId($entry)
    {
        /** @var \VRPayment\Sdk\Model\LabelDescriptor $entry */
        return $entry->getId();
    }
}
