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
namespace VRPayment\Payment\Model\Config;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory to create DOM objects.
 */
class DomFactory
{

    const CLASS_NAME = Dom::class;

    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     *
     * @param ObjectManagerInterface $objectManger
     */
    public function __construct(ObjectManagerInterface $objectManger)
    {
        $this->objectManager = $objectManger;
    }

    /**
     * Create DOM object
     *
     * @param array $arguments
     * @return Dom
     */
    public function createDom(array $arguments = [])
    {
        return $this->objectManager->create(self::CLASS_NAME, $arguments);
    }
}