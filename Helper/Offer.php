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
namespace Smile\RetailerOffer\Helper;

use Lof\MarketPlace\Model\SellerFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Smile\Offer\Api\Data\OfferInterface;
use Smile\StoreLocator\CustomerData\CurrentStore;
use function PHPUnit\Framework\throwException;

/**
 * Generic Helper for Retailer Offer
 *
 * @category Smile
 * @package  Smile\RetailerOffer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Offer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Smile\Offer\Api\OfferManagementInterface
     */
    private $offerManagement;

    /**
     * @var \Smile\StoreLocator\CustomerData\CurrentStore
     */
    private $currentStore;

    /**
     * @var OfferInterface[]
     */
    private $offersCache = [];

    /**
     * ProductPlugin constructor.
     *
     * @param \Magento\Framework\App\Helper\Context         $context         Helper context.
     * @param \Smile\Offer\Api\OfferManagementInterface     $offerManagement The offer Management
     * @param \Smile\StoreLocator\CustomerData\CurrentStore $currentStore    Current Store Provider
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Smile\Offer\Api\OfferManagementInterface $offerManagement,
        \Smile\StoreLocator\CustomerData\CurrentStore $currentStore,
        SellerFactory $sellerFactory,
        \Magento\Inventory\Model\SourceRepository $sourceRepository,
        \Magento\InventoryApi\Api\Data\SourceInterface $sourceInterface,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSaveInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItemInterface
    ) {
        $this->offerManagement = $offerManagement;
        $this->currentStore    = $currentStore;
        $this->sellerFactory = $sellerFactory;
        $this->sourceRepository = $sourceRepository;
        $this->sourceInterface = $sourceInterface;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemInterface = $sourceItemInterface;
        parent::__construct($context);
    }

    /**
     * Retrieve Offer for the product by retailer id.
     *
     * @param ProductInterface $product    The product
     * @param integer          $retailerId The retailer Id
     *
     * @return \Smile\Offer\Api\Data\OfferInterface
     */
    public function getOffer($product, $retailerId)
    {
        $offer = null;

        if ($product->getId() && $retailerId) {
            $cacheKey = implode('_', [$product->getId(), $retailerId]);

            if (false === isset($this->offersCache[$cacheKey])) {
                $offer                        = $this->offerManagement->getOffer($product->getId(), $retailerId);
                $this->offersCache[$cacheKey] = $offer;
            }

            $offer = $this->offersCache[$cacheKey];
        }

        return $offer;
    }

    /**
     * Retrieve Current Offer for the product.
     *
     * @param ProductInterface $product The product
     *
     * @return \Smile\Offer\Api\Data\OfferInterface
     */
    public function getCurrentOffer($product)
    {
        $offer = null;

        if ($this->currentStore->getRetailer() && $this->currentStore->getRetailer()->getId()) {
            $offer = $this->getOffer($product, $this->currentStore->getRetailer()->getId());
        }

        return $offer;
    }

    public function getRetailerSource($sellerId) {
        $seller = $this->getSeller($sellerId);
        try {
            return $this->sourceRepository->get($seller->getUrlKey());
        } catch (\Exception $e){
            return null;
        }
    }

    public function getSeller($sellerId)
    {
        return $this->sellerFactory->create()->load($sellerId);
    }

    public function createRetailerSource($sellerId)
    {
        $seller = $this->getSeller($sellerId);
        if (!$this->getRetailerSource($sellerId)) {
            try {
                $offerSource = $this->sourceInterface;
                $offerSource->setSourceCode($seller->getUrlKey());
                if ($seller->getData('status') == \Lof\MarketPlace\Model\Seller::STATUS_ENABLED){
                    $offerSource->setEnabled($seller->getData('status'));
                }else {
                    $offerSource->setEnabled(0);
                }
                $offerSource->setName($seller->getData('name'));
                $offerSource->setCountryId($seller->getData('country_id'));
                $offerSource->setRegionId($seller->getData('region_id'));
                $offerSource->setCity($seller->getData('city'));
                $offerSource->setPostcode($seller->getData('postcode'));
                $this->sourceRepository->save($offerSource);
            } catch (\Exception $e){

            }
        }
    }

    public function getOfferSourceItem($sourceCode, $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();
        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }

    public function saveOfferSourceItem($sourceCode, $sku, $qty, $isInStock)
    {
        try {
            $sourceItem = $this->sourceItemInterface;
            $sourceItem->setSourceCode($sourceCode);
            $sourceItem->setSku($sku);
            $sourceItem->setQuantity($qty);
            $sourceItem->setStatus($isInStock);
            $this->sourceItemsSaveInterface->execute([$sourceItem]);
        } catch (\Exception $e){
            throw $e;
        }
    }
}
