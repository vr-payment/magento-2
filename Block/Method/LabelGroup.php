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
namespace VRPayment\Payment\Block\Method;

use VRPayment\Sdk\Model\LabelDescriptorGroup;

/**
 * Holds information about a label group that are needed to render the labels in the backend.
 */
class LabelGroup
{

    /**
     *
     * @var LabelDescriptorGroup
     */
    private $descriptor;

    /**
     *
     * @var Label[]
     */
    private $labels = [];

    /**
     *
     * @param LabelDescriptorGroup $descriptor
     * @param Label[] $labels
     */
    public function __construct(LabelDescriptorGroup $descriptor, array $labels)
    {
        $this->descriptor = $descriptor;
        $this->labels = $labels;
    }

    /**
     * Gets the group descriptor's ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->descriptor->getId();
    }

    /**
     * Gets the group descriptor's name.
     *
     * @return array
     */
    public function getName()
    {
        return $this->descriptor->getName();
    }

    /**
     * Gets the group descriptor's weight.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->descriptor->getWeight();
    }

    /**
     *
     * @return Label[]
     */
    public function getLabels()
    {
        return $this->labels;
    }
}