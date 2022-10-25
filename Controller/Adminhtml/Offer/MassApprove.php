<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\RetailerOffer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\RetailerOffer\Controller\Adminhtml\Offer;

use Smile\RetailerOffer\Controller\Adminhtml\AbstractOffer;

/**
 * Delete Controller for Offer
 *
 * @category Smile
 * @package  Smile\RetailerOffer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MassApprove extends AbstractOffer
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $collection = $this->filter->getCollection($this->offerCollectionFactory->create());
        $collectionSize = $collection->getSize();
        foreach ($collection as $item) {
            $sellerOffer = $this->sellerOfferRepository->getById($item->getId());
            $sellerOffer->setData('request_status', \Lofmp\SellerOffer\Model\SellerOffer::STATUS_APPROVED);
            $this->sellerOfferRepository->saveSellerOffer($sellerOffer);
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been approved.', $collectionSize));
        return $resultRedirect->setPath('*/*/index');
    }
}
