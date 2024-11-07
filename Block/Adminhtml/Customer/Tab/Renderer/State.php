<?php
/**
 * VR payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Block\Adminhtml\Customer\Tab\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use VRPayment\Sdk\Model\CreationEntityState;

/**
 * Block to render the state grid column of the token grid.
 */
class State extends AbstractRenderer
{

    public function render(DataObject $row)
    {
        switch ($row->getData($this->getColumn()
            ->getIndex())) {
            case CreationEntityState::ACTIVE:
                return \__('Active');
            case CreationEntityState::INACTIVE:
                return \__('Inactive');
            default:
                return \__('Unknown State');
        }
    }
}