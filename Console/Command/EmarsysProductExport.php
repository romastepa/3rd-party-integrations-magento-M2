<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Console\Command;

use Emarsys\Emarsys\Helper\Data;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emarsys\Emarsys\Model\Product;

/**
 * Command for deployment of Sample Data
 */
class EmarsysProductExport extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Product
     */
    private $product;

    /**
     * EmarsysProductExport constructor.
     *
     * @param State $state
     * @param Product $product
     */
    public function __construct(
        State $state,
        Product $product
    ) {
        $this->state = $state;
        $this->product = $product;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('emarsys:export:product')
            ->setDescription('Product bulk export');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_GLOBAL);
        $output->writeln('');
        $output->writeln('<info>Starting product bulk export.</info>');

        try {
            $this->product->consolidatedCatalogExport(Data::ENTITY_EXPORT_MODE_MANUAL);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }

        $error = error_get_last();
        if (!empty($error['message'])) {
            $output->writeln($error);
        }

        $output->writeln('<info>Product bulk export complete</info>');
        $output->writeln('');
    }
}
