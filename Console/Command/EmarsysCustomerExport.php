<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for deployment of Sample Data
 */
class EmarsysCustomerExport extends Command
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Emarsys\Emarsys\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * EmarsysCustomerExport constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emarsys\Emarsys\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Framework\App\State $state
    ) {
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                '--from="Y-m-d" (2017-12-31)'
            ),
            new InputOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL,
                '--to="Y-m-d" (2017-12-31)'
            ),
            new InputOption(
                'page',
                null,
                InputOption::VALUE_OPTIONAL,
                '--page="1"'
            ),
        ];

        $this->setName('emarsys:export:customer')
            ->setDescription('Customer bulk export (--from=\'Y-m-d\' (2016-01-31) --to=\'Y-m-d\' (2017-12-31)) --page=\'1\'')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $output->writeln('');
        $output->writeln('<info>Starting customer bulk export.</info>');

        /** @var \Magento\Store\Model\Store $store */
        foreach ($this->storeManager->getStores() as $storeId => $store) {
            if ($store->getConfig(\Emarsys\Emarsys\Helper\Data::XPATH_EMARSYS_ENABLED) && $store->getConfig(\Emarsys\Emarsys\Helper\Data::XPATH_EMARSYS_ENABLED)) {
                $data = [];
                $data['page'] = ($input->getOption('page') && !empty($input->getOption('page'))) ? $input->getOption('page') : 1;
                $data['fromDate'] = ($input->getOption('from') && !empty($input->getOption('from'))) ? $input->getOption('from') . ' 00:00:01' : '';
                $data['toDate'] = ($input->getOption('to') && !empty($input->getOption('to'))) ? $input->getOption('to') . ' 23:59:59' : '';
                $data['website'] = $store->getWebsiteId();
                $data['storeId'] = $storeId;
                try {
                    \Magento\Framework\App\ObjectManager::getInstance()->get(\Emarsys\Emarsys\Model\Api\Contact::class)->syncFullContactUsingApi(
                        \Emarsys\Emarsys\Helper\Cron::CRON_JOB_CUSTOMER_BULK_EXPORT_API,
                        $data
                    );
                    $output->writeln('Processed <info>store: ' . $storeId . '</info>');
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                    $output->writeln($e->getTrace());
                }
            }
        }

        $error = error_get_last();
        if (!empty($error['message'])) {
            $output->writeln($error);
        }

        $output->writeln('<info>Customer bulk export complete</info>');
        $output->writeln('');
    }
}
