<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Create webhooks for this app on the shop.
 */
class CreateWebhooksAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IApiHelper $apiHelper The API helper.
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param int $shopId The shop ID.
     *
     * @return array
     */
    public function __invoke(int $shopId): array
    {
        /**
         * Checks if a webhooks exists already in the shop.
         *
         * @param array $webhook  The webhook config.
         * @param array $webhooks The current webhooks to search.
         *
         * @return bool
         */
        $exists = function (array $webhook, array $webhooks): bool {
            foreach ($webhooks as $shopWebhook) {
                if ($shopWebhook->address === $webhook['address']) {
                    // Found the webhook in our list
                    return true;
                }
            }
    
            return false;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Set the API instance
        $this->apiHelper->setInstance($shop->api());

        // Get the webhooks in config
        $configWebhooks = Config::get('shopify-app.webhooks');

        // Get the webhooks existing in for the shop
        $webhooks = $this->apiHelper->getWebhooks();

        $created = [];
        foreach ($configWebhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$exists($webhook, $webhooks)) {
                // It does not... create the webhook
                $this->api->createWebhook($webhook);

                // Keep track of what was created
                $created[] = $webhook;
            }
        }

        return $created;
    }
}