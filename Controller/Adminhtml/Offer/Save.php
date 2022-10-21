<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\RetailerOffer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\RetailerOffer\Controller\Adminhtml\Offer;
;
use Smile\Offer\Api\Data\OfferInterface;
use Smile\RetailerOffer\Controller\Adminhtml\AbstractOffer;

/**
 * Retailer Offer Adminhtml Save controller.
 *
 * @category Smile
 * @package  Smile\RetailerOffer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Save extends AbstractOffer
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();
        $redirectBack = $this->getRequest()->getParam('back', false);

        if ($data) {
            $identifier = $this->getRequest()->getParam('offer_id');
            $model = $this->sellerOfferInterface->create();
            $addQtyToProduct = false;
            if ($identifier) {
                $model->load($identifier);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This offer no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            } else{
                $retailerId = $data['seller_id'];
                $productId = $data['product_id'];
                $offerCheck = $this->offerCollectionFactory->create()->addFieldToFilter('seller_id', $retailerId)
                    ->addFieldToFilter('product_id', $productId);
                if ($offerCheck->getSize()){
                    $this->messageManager->addErrorMessage(__('This product is existed in in offer id %1.', $offerCheck->getFirstItem()->getId()));
                    return $resultRedirect->setPath('*/*/');
                }
                $addQtyToProduct = true;
            }

            try {
                if ($data['qty'] == 0){
                    $data['is_in_stock'] = 0;
                }
                $model->loadPost($data);
                $this->_getSession()->setPageData($data);
                $this->offerRepository->save($model);
                $this->sellerOfferRepository->saveSellerOffer($model);
                if ($addQtyToProduct) {
                    $stockItem = $this->stockRegistry->getStockItem($model->getData('product_id'));
                    $oldQty = $stockItem->getQty();
                    $newQty = $oldQty + $data['qty'];
                    $stockItem->setQty($newQty);
                    $stockItem->save();
                }
                $this->messageManager->addSuccessMessage(__('You saved the offer %1.', $model->getId()));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                if ($redirectBack
                    || (!is_null($model->getOverlapOffers()) && count($model->getOverlapOffers()))
                ) {
                    return $resultRedirect->setPath('*/*/edit', ['offer_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);
                $returnParams = ['offer_id' => $this->getRequest()->getParam('offer_id')];

                return $resultRedirect->setPath('*/*/edit', $returnParams);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
