/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\RetailerOffer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

/*jshint browser:true jquery:true*/
/*global alert*/

define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko',
    'uiRegistry',
    'Smile_Map/js/model/markers',
    'smile-storelocator-store-collection',
    'mage/translate'
    ], function ($, Component, storage, ko, registry, Markers, StoreCollection) {

    "use strict";

    var retailer = storage.get('current-store');

    return Component.extend({

        /**
         * Constructor
         */
        initialize: function () {
            this._super();
            var offers = new StoreCollection({items : this.storeOffers});
            offers.getList().forEach(function(marker) {
                marker.distance = ko.observable(0);
            });
            this.storeOffers = ko.observable(offers.getList());
            this.displayedOffers = ko.observable(offers.getList());
            this.initGeocoderBinding();
        },

        /**
         * Init the geocoding component binding
         */
        initGeocoderBinding: function() {
            registry.get(this.name + '.geocoder', function (geocoder) {
                this.geocoder = geocoder;
                geocoder.currentResult.subscribe(function (result) {
                    if (result && result.location) {
                        var offers = geocoder.filterMarkersListByPositionRadius(this.storeOffers(), result.location);
                        this.displayedOffers(offers);
                    } else {
                        this.displayedOffers(this.storeOffers);
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Check if there is a current store
         *
         * @returns {boolean}
         */
        hasStore : function () {
            return (retailer().entity_id !== null) && (retailer().entity_id !== undefined);
        },

        /**
         * Retrieve link label
         *
         * @returns {string}
         */
        getLinkLabel : function () {
            return $.mage.__('Offers');
        },

        /**
         * Get current store name
         *
         * @returns {*}
         */
        getStoreName : function () {
            return retailer().name;
        },

        /**
         * Get stock label depending of the status
         *
         * @returns {string}
         */
        getStockLabel: function () {
            return this.getIsInStock() === true ? $.mage.__('In Stock') : $.mage.__('Out Of Stock');
        },

        /**
         * Check if the product is in stock for the currently selected store.
         *
         * @returns {boolean}
         */
        getIsInStock: function() {
            var result = false;
            if (this.hasStore()) {
                var retailerId = retailer().entity_id;
                this.storeOffers().forEach(function(store) {
                    if ((store.isAvailable) && (parseInt(store.sellerId, 10) === parseInt(retailerId, 10))) {
                        result = true;
                    }
                }, this);
            }

            return result;
        }
    });
});
