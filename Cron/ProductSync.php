<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Product as EmarsysProductModel;
use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Model\ProductExportAsync as ProductExportAsync;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data;

/**
 * Class ProductSync
 */
class ProductSync
{
    /**
     * @var EmarsysProductModel
     */
    protected $emarsysProductModel;

    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var ProductExportAsync
     */
    private $productAsync;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ProductSync constructor.
     *
     * @param EmarsysProductModel $emarsysProductModel
     * @param Logs $emarsysLogs
     * @param ProductExportAsync $productAsync
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        EmarsysProductModel $emarsysProductModel,
        Logs $emarsysLogs,
        ProductExportAsync $productAsync,
        StoreManagerInterface $storeManager
    ) {
        $this->emarsysProductModel = $emarsysProductModel;
        $this->emarsysLogs = $emarsysLogs;
        $this->productAsync = $productAsync;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $async = false;
            foreach ($this->storeManager->getStores(true) as $store) {
                $async = $store->getConfig('emarsys_predict/enable/async');
                if ($async) {
                    break;
                }
            }
            if (!$async) {
                $this->emarsysProductModel->consolidatedCatalogExport(Data::ENTITY_EXPORT_MODE_AUTOMATIC);
            } else {
                $this->productAsync->run(Data::ENTITY_EXPORT_MODE_AUTOMATIC);
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                'ProductSync',
                $e->getMessage(),
                0,
                'ProductSync::execute()'
            );
        }
        return true;
    }
}
