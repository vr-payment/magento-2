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
use VRPayment\Sdk\Service\LabelDescriptionGroupService;

/**
 * Provider of label descriptor group information from the gateway.
 */
class LabelDescriptorGroupProvider extends AbstractProvider
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
            'vrpayment_payment_label_descriptor_groups',
            \VRPayment\Sdk\Model\LabelDescriptorGroup::class
        );
        $this->apiClient = $apiClient;
    }

    /**
     * Fetch label descriptor groups from the API.
     *
     * @return mixed
     */
    protected function fetchData()
    {
        return $this->apiClient->getService(LabelDescriptionGroupService::class)->all();
    }

    /**
     * Get label descriptor group ID from the given entry.
     *
     * @param \VRPayment\Sdk\Model\LabelDescriptorGroup $entry
     * @return int
     */
    protected function getId($entry)
    {
        /** @var \VRPayment\Sdk\Model\LabelDescriptorGroup $entry */
        return $entry->getId();
    }
}
