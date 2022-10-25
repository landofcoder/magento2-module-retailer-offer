<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\RetailerOffer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\RetailerOffer\Ui\Component\Offer\Listing;

use Lofmp\Retailer\Model\RetailerRepository;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Data Provider for UI Retailer Offer
 *
 * @category Smile
 * @package  Smile\RetailerOffer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    private $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    private $addFilterStrategies;

    /**
     * Construct
     *
     * @param string                                                    $name                Component name
     * @param string                                                    $primaryFieldName    Primary field Name
     * @param string                                                    $requestFieldName    Request field name
     * @param CollectionFactory                                         $collectionFactory   The collection factory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]  $addFieldStrategies  Add field Strategy
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies Add filter Strategy
     * @param array                                                     $meta                Component Meta
     * @param array                                                     $data                Component extra data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        $collectionFactory,
        \Smile\Offer\Model\OfferFactory $offerFactory,
        \Lofmp\SellerOffer\Helper\Data $offerHelper,
        RetailerRepository $retailerRepository,
        \Lofmp\SellerOffer\Model\SellerOfferFactory $sellerOffer,
        \Lofmp\SellerOffer\Model\ResourceModel\Offer $offer,
        \Magento\Inventory\Model\SourceItemFactory $sourceItem,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection = $collectionFactory->create();

        $this->collection->addFilterToMap('offer_id', 'main_table.offer_id');

        $this->addFieldStrategies  = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
        $this->offerFactory = $offerFactory;
        $this->offerHelper = $offerHelper;
        $this->retailerRepository = $retailerRepository;
        $this->sellerOffer = $sellerOffer;
        $this->sourceItem = $sourceItem;
        $this->offer = $offer;
    }

    /**
     * Add field to select
     *
     * @param string|array $field The field
     * @param string|null  $alias Alias for the field
     *
     * @return void
     */
    public function addField($field, $alias = null)
    {
        if (isset($this->addFieldStrategies[$field])) {
            $this->addFieldStrategies[$field]->addField($this->getCollection(), $field, $alias);

            return;
        }

        parent::addField($field, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if (isset($this->addFilterStrategies[$filter->getField()])) {
            $this->addFilterStrategies[$filter->getField()]
                ->addFilter(
                    $this->getCollection(),
                    $filter->getField(),
                    [$filter->getConditionType() => $filter->getValue()]
                );

            return;
        }

        parent::addFilter($filter);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->setOfferToFilter();
            $this->getCollection()->load();
        }

        $items = $this->getCollection()->toArray();
        $items = $items['items'];
        if (count($items)){
            $totalQty = $this->offerHelper->getSoldQtyOfferProducts();
        }
        foreach ($items as $key => $item) {
            if (isset($item['offer_id'])){
                try {
                    $sellerOffer = $this->sellerOffer->create()->load($item['offer_id']);
//                    $sourceItem = $this->sourceItem->create()->load($sellerOffer->getData('source_item_id'));
                    $items[$key]['qty'] =  $sellerOffer->getData('qty');
                    $items[$key]['is_in_stock'] = $sellerOffer->getData('is_in_stock');
                    $items[$key]['price'] = $sellerOffer->getPrice();
                    $items[$key]['special_price'] = $sellerOffer->getSpecialPrice();
                    $items[$key]['is_available'] = $sellerOffer->getIsAvailable();
                    $qtySold = 0;
                    if (isset($totalQty[(int)$sellerOffer->getProductId()][$item['entity_id']])){
                        $qtySold = $totalQty[(int)$sellerOffer->getProductId()][$item['entity_id']];
                    }
                    $items[$key]['qty_sold'] = $qtySold;
                } catch (\Exception $e){

                }
            }
        }
        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => $items,
        ];
    }

    public function setOfferToFilter()
    {
        $this->getCollection()->getSelect()
            ->join(
                ['seller_offer' => $this->getCollection()->getResource()->getTable("lofmp_offer")],
                'main_table.offer_id = seller_offer.offer_id',
                [
                    'lof_seller_id',
                    'comment',
                    'request_status'
                ]
            )
            ->group(
                'main_table.offer_id'
            );
        return $this;
    }
}
