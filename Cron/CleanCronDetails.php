<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Cron;

use Emarsys\Emarsys\Model\Logs;
use Emarsys\Emarsys\Model\EmarsysCronDetails;

class CleanCronDetails
{
    /**
     * @var Logs
     */
    protected $emarsysLogs;

    /**
     * @var EmarsysCronDetails
     */
    protected $emarsysCronDetails;

    /**
     * CleanCronDetails constructor.
     *
     * @param Logs $emarsysLogs
     * @param EmarsysCronDetails $emarsysCronDetails
     */
    public function __construct(
        Logs $emarsysLogs,
        EmarsysCronDetails $emarsysCronDetails
    ) {
        $this->emarsysLogs = $emarsysLogs;
        $this->emarsysCronDetails = $emarsysCronDetails;
    }

    public function execute()
    {
        try {
            //clear cron details table
            $this->emarsysCronDetails->clearEmarsysCronDetails();
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                'CleanCronDetails',
                $e->getMessage(),
                0,
                'CleanCronDetails::execute()'
            );
        }
    }
}
