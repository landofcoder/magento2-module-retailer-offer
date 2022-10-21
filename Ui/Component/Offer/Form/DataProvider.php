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

namespace Smile\RetailerOffer\Ui\Component\Offer\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Registry;
use Smile\Offer\Api\OfferRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Data Provider for Retailer Offer Edit Form
 *
 * @category Smile
 * @package  Smile\RetailerOffer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OfferRepositoryInterface
     */
    private $offerRepository;

    /**
     * DataProvider constructor.
     *
     * @param string                   $name              The name
     * @param string                   $primaryFieldName  Primary field Name
     * @param string                   $requestFieldName  Request field Name
     * @param array                    $collectionFactory The collection factory
     * @param Registry                 $registry          The Registry
     * @param RequestInterface         $request           The Request
     * @param OfferRepositoryInterface $offerRepository   The Offer Repository
     * @param array                    $meta              Component Meta
     * @param array                    $data              Component Data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        $collectionFactory,
        Registry $registry,
        RequestInterface $request,
        OfferRepositoryInterface $offerRepository,
        \Magento\Inventory\Model\SourceItemFactory $sourceItem,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->registry = $registry;
        $this->offerRepository = $offerRepository;
        $this->request = $request;
        $this->sourceItem = $sourceItem;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Retrieve Current data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $offer = $this->getCurrentOffer();
        $this->setOfferToFilter();
        $offerItem = $this->collection->getFirstItem();
        if ($offer) {
            $offerData = $offer->getData();
            if (!empty($offerData)) {
                $offerData['request_status'] = $offerItem->getData('request_status');
                $this->loadedData[$offer->getId()] = $offerData;
            }
        }

        return $this->loadedData;
    }

    /**
     * Get current offer
     *
     * @return \Smile\Offer\Api\Data\OfferInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentOffer()
    {
        $offer = $this->registry->registry('current_offer');

        if ($offer) {
            return $offer;
        }

        $requestId = $this->request->getParam($this->requestFieldName);
        if ($requestId) {
            $offer = $this->offerRepository->getById($requestId);
        }

        if (!$offer || !$offer->getId()) {
            $offer = $this->collection->getNewEmptyItem();
        }

        return $offer;
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
