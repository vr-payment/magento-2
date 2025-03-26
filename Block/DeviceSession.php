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
namespace VRPayment\Payment\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class DeviceSession extends \Magento\Framework\View\Element\Template
{

    /**
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, array $data = [])
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     *
     * @return string
     */
    public function getSessionIdentifierUrl()
    {
        return $this->getUrl('vrpayment_payment/checkout/deviceSession', ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     *
     * @return string
     */
    public function getScriptUrl()
    {
        $device = $this->scopeConfig->getValue('vrpayment_payment/checkout/fingerprint', ScopeInterface::SCOPE_STORE, $this->_storeManager->getStore());
        if ($device!=1) {
            return false;
        }

        $baseUrl = \rtrim($this->scopeConfig->getValue('vrpayment_payment/general/base_gateway_url'), '/');
        $spaceId = $this->scopeConfig->getValue('vrpayment_payment/general/space_id',
            ScopeInterface::SCOPE_STORE, $this->_storeManager->getStore());

        if (! empty($spaceId)) {
            return $baseUrl . '/s/' . $spaceId . '/payment/device.js?sessionIdentifier=';
        }
    }
}