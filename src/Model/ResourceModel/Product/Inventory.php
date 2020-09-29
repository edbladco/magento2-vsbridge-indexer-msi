<?php
/**
 * @package   Divante\VsbridgeIndexerMsi
 * @author    Agata Firlejczyk <afirlejczyk@divante.pl>
 * @copyright 2019 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerMsi\Model\GetStockIndexTableByStore;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\IndexStructure;

/**
 * Class Inventory
 */
class Inventory
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var GetStockIndexTableByStore
     */
    private $getSockIndexTableByStore;

    /**
     * Inventory constructor.
     *
     * @param GetStockIndexTableByStore $getSockIndexTableByStore
     * @param ResourceConnection $resourceModel
     */
    public function __construct(
        GetStockIndexTableByStore $getSockIndexTableByStore,
        ResourceConnection $resourceModel
    ) {
        $this->getSockIndexTableByStore = $getSockIndexTableByStore;
        $this->resource = $resourceModel;
    }

    /**
     * @param int $storeId
     * @param array $skuList
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadInventory(int $storeId, array $skuList): array
    {
        return $this->getInventoryData($storeId, $skuList);
    }

    /**
     * @param int $storeId
     * @param array $skuList
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadChildrenInventory(int $storeId, array $skuList): array
    {
        return $this->getInventoryData($storeId, $skuList);
    }

    /**
     * @param int $storeId
     * @param array $productIds
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getInventoryData(int $storeId, array $productIds): array
    {
        $connection = $this->resource->getConnection();
        $stockItemTableName = $this->getSockIndexTableByStore->execute($storeId);
        $stockId = 2;

/*        $expressionsToSelect = [
            new \Zend_Db_Expr(
                sprintf('%s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) AS qty', $stockItemTableName)
            ),
            new \Zend_Db_Expr(
                sprintf('CASE WHEN %s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) > 0 || SUM(reservation_table.quantity) IS NULL THEN %s.is_salable ELSE 0 END AS is_in_stock', $stockItemTableName, $stockItemTableName)
            ),
            new \Zend_Db_Expr(
                sprintf('CASE WHEN %s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) > 0 || SUM(reservation_table.quantity) IS NULL THEN %s.is_salable ELSE 0 END AS stock_status', $stockItemTableName, $stockItemTableName)
            )
        ];
*/
        $expressionsToSelect = [
            new \Zend_Db_Expr(
                sprintf('%s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) AS qty', $stockItemTableName)
            ),
            new \Zend_Db_Expr(
                sprintf('
CASE WHEN %s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) > 0 THEN
		%s.is_salable
	WHEN SUM(reservation_table.quantity) IS NULL AND %s.quantity > 0 THEN 
		%s.is_salable
	ELSE
		0
	END AS is_in_stock', $stockItemTableName, $stockItemTableName, $stockItemTableName, $stockItemTableName)
            ),
            new \Zend_Db_Expr(
                sprintf('
CASE WHEN %s.quantity + SUM(COALESCE(reservation_table.quantity, 0)) > 0 THEN
		%s.is_salable
	WHEN SUM(reservation_table.quantity) IS NULL AND %s.quantity > 0 THEN 
		%s.is_salable
	ELSE
		0
	END AS stock_status', $stockItemTableName, $stockItemTableName, $stockItemTableName, $stockItemTableName)
            ),
        ];

        $select = $connection->select()
            ->from(
                $stockItemTableName,
                [
                    'sku' => IndexStructure::SKU,
                    // 'qty' => IndexStructure::QUANTITY,
                    // 'is_in_stock' => IndexStructure::IS_SALABLE,
//                    'stock_status' => IndexStructure::IS_SALABLE,
                ]
            );
//            ->where(IndexStructure::SKU . ' IN (?)', $productIds);

        $select->joinLeft(
            ['reservation_table' => $this->resource->getTableName('inventory_reservation')],
            sprintf('reservation_table.sku=%s.sku AND %d = reservation_table.stock_id', $stockItemTableName, $stockId),
            $expressionsToSelect
        );
        $select->group(
            [
$stockItemTableName . "." . IndexStructure::SKU
            ]
        );

$select->where($stockItemTableName . "." . IndexStructure::SKU . ' IN (?)', $productIds);
//file_put_contents("/tmp/70.log", $select, FILE_APPEND);
//die;
        return $connection->fetchAssoc($select);
    }
}
