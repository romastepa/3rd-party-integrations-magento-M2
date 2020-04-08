<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Product as EmarsysProductModel;
use Emarsys\Emarsys\Model\Logs;

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
     * ProductSync constructor.
     *
     * @param EmarsysProductModel $emarsysProductModel
     * @param Logs $emarsysLogs
     */
    public function __construct(
        EmarsysProductModel $emarsysProductModel,
        Logs $emarsysLogs
    ) {
        $this->emarsysProductModel = $emarsysProductModel;
        $this->emarsysLogs = $emarsysLogs;
    }

    public function execute()
    {
        try {
            set_time_limit(0);
            $this->emarsysProductModel->consolidatedCatalogExport(
                \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_AUTOMATIC
            );
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
