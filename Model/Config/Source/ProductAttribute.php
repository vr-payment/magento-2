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
namespace VRPayment\Payment\Model\Config\Source;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Provides product attributes as array options.
 */
class ProductAttribute implements \Magento\Framework\Option\ArrayInterface
{

    /**
     *
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     *
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     *
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder, SortOrderBuilder $sortOrderBuilder)
    {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    public function toOptionArray()
    {
        $options = [];
        $sortOrder = $this->sortOrderBuilder->setField('attribute_code')
            ->setAscendingDirection()
            ->create();
        $attributes = $this->attributeRepository->getList(
            $this->searchCriteriaBuilder->addSortOrder($sortOrder)
                ->create());
        foreach ($attributes->getItems() as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getDefaultFrontendLabel()
            ];
        }
        return $options;
    }
}