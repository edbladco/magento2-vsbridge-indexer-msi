<?php
/**
 * @package  Divante\VsbridgeIndexerMsi
 * @author Agata Firlejczyk <afirlejczyk@divante.pl>
 * @copyright 2019 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model;

use Divante\VsbridgeIndexerCatalog\Api\LoadInventoryInterface;
use Divante\VsbridgeIndexerMsi\Model\ResourceModel\Product\Inventory as InventoryResource;

/**
 * Class LoadInventory
 */
class LoadInventory implements LoadInventoryInterface
{
    /**
     * @var InventoryResource
     */
    private $resource;

    /**
     * LoadChildrenInventory constructor.
     *
     * @param InvetoryResource $resource
     */
    public function __construct(InventoryResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $indexData, int $storeId): array
    {
        $productIdBySku = $this->getIdBySku($indexData);
        $rawInventory = $this->resource->loadInventory($storeId, array_keys($productIdBySku));
        $rawInventoryByProductId = [];

        foreach ($rawInventory as $sku => $productInventory) {
            if ( !empty($productIdBySku[$sku]) ) {
                $productId = $productIdBySku[$sku];
                $productInventory['product_id'] = $productId;
                unset($productInventory['sku']);
                $rawInventoryByProductId[$productId] = $productInventory;
            }
        }

        return $rawInventoryByProductId;
    }

    /**
     * @param array $indexData
     *
     * @return array
     */
    private function getIdBySku(array $indexData): array
    {
        $idBySku = [];

        foreach ($indexData as $productId => $product) {
            $sku = $product['sku'];
            $idBySku[$sku] = $productId;
        }

        return $idBySku;
    }
}
