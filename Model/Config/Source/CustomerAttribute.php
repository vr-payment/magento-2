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
namespace VRPayment\Payment\Model\Config\Source;

/**
 * Provides customer attributes as array options.
 */
class CustomerAttribute implements \Magento\Framework\Option\ArrayInterface
{

    /**
     *
     * @var \Magento\Customer\Model\Form
     */
    private $customerForm;

    /**
     *
     * @param \Magento\Customer\Model\Form $customerForm
     */
    public function __construct(\Magento\Customer\Model\Form $customerForm)
    {
        $this->customerForm = $customerForm;
    }

    public function toOptionArray()
    {
        $options = [];
        $attributes = $this->customerForm->setFormCode('adminhtml_customer')->getAttributes();
        /** @var \Magento\Eav\Model\Attribute $attribute */
        foreach ($attributes as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontend()->getLocalizedLabel()
            ];
        }
        return $options;
    }
}