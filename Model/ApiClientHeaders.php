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
namespace VRPayment\Payment\Model;

use VRPayment\Sdk\ApiClient;

/**
 * Service to provide VR Payment API client.
 */
class ApiClientHeaders
{

    /**
     * @var string
     */
    public const SHOP_SYSTEM = 'x-meta-shop-system';

    /**
     * @var string
     */
    public const SHOP_SYSTEM_VERSION = 'x-meta-shop-system-version';

    /**
     * @var string
     */
    public const SHOP_SYSTEM_AND_VERSION = 'x-meta-shop-system-and-version';

    /**
     * Sets the headers.
     *
     * @param \VRPayment\Sdk\ApiClient $apiClient
     * @return void
     */
    public function addHeaders(ApiClient &$apiClient)
    {
        $data = self::getDefaultData();
        foreach ($data as $key => $value) {
            $apiClient->addDefaultHeader($key, $value);
        }
    }

    /**
     * @return array<mixed>
     */
    protected static function getDefaultData()
    {

        // todo refactor using DI: https://www.rohanhapani.com/how-to-find-out-version-of-magento-2-programmatically/;
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  
        $productMetadata = $objectManager->get('\Magento\Framework\App\ProductMetadataInterface'); 
        $shop_version = $productMetadata->getVersion();

        [$major_version, $minor_version, $rest] = explode('.', $shop_version, 3);
        return [
            self::SHOP_SYSTEM             => 'magento',
            self::SHOP_SYSTEM_VERSION     => $shop_version,
            self::SHOP_SYSTEM_AND_VERSION => 'magento-' . $major_version . '.' . $minor_version,
        ];
    }
}