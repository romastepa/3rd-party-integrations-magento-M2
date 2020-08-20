<?php
/**
 * @category  Emarsys
 * @package   Emarsys_Emarsys
 * @copyright Copyright (c) 2020 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Console\Command;

use Emarsys\Emarsys\Helper\Data as EmarsysHelper;
use Emarsys\Emarsys\Model\ApiExport;
use Emarsys\Emarsys\Model\Emarsysproductexport as ProductExportModel;
use Exception;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emarsys\Emarsys\Model\Product;
use Emarsys\Emarsys\Model\ProductExportAsync as ProductExportAsync;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\Serializer\Serialize as Serializer;
use Emarsys\Emarsys\Model\ProcessPro as ProcessModel;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Command for deployment of Sample Data
 */
class ProcessPro extends Command
{
    const EMARSYS_DELIMITER = '{EMARSYS}';
    const BATCH_SIZE = 500;

    protected $_preparedData = [];
    protected $_mapHeader = ['item'];
    protected $_processedStores = [];
    protected $_delimiter = ',';
    protected $_enclosure = '"';

    protected $currencyCodeTo = [];
    protected $rate = [];

    /**
     * @var State
     */
    private $state;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var ProductExportAsync
     */
    private $productAsync;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var ProductExportModel
     */
    protected $productExportModel;

    /**
     * @var ApiExport
     */
    protected $apiExport;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var ProcessModel
     */
    protected $process;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var File
     */
    protected $file;

    /**
     * EmarsysProductExport constructor.
     *
     * @param State $state
     * @param Product $product
     * @param ProductExportAsync $productAsync
     * @param StoreManagerInterface $storeManager
     * @param DeploymentConfig $deploymentConfig
     * @param ProductExportModel $productExportModel
     * @param ApiExport $apiExport
     * @param EmarsysHelper $emarsysHelper
     * @param Serializer $serializer
     * @param ProcessModel $process
     * @param CurrencyFactory $currencyFactory
     * @param File $file
     */
    public function __construct(
        State $state,
        Product $product,
        ProductExportAsync $productAsync,
        StoreManagerInterface $storeManager,
        DeploymentConfig $deploymentConfig,
        ProductExportModel $productExportModel,
        ApiExport $apiExport,
        EmarsysHelper $emarsysHelper,
        Serializer $serializer,
        ProcessModel $process,
        CurrencyFactory $currencyFactory,
        File $file
    ) {
        $this->state = $state;
        $this->product = $product;
        $this->productAsync = $productAsync;
        $this->storeManager = $storeManager;
        $this->deploymentConfig = $deploymentConfig;
        $this->productExportModel = $productExportModel;
        $this->apiExport = $apiExport;
        $this->emarsysHelper = $emarsysHelper;
        $this->serializer = $serializer;
        $this->process = $process;
        $this->currencyFactory = $currencyFactory;
        $this->file = $file;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('emarsys:dump:prod')
            ->setDescription('Product dump process Prod');
        parent::configure();
    }

    public function cron()
    {

    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_GLOBAL);
        $output->writeln('');
        $output->writeln('<info>Starting dump process</info>');
        $output->writeln('');
        $output->writeln('<info>' . date('Y-m-d H:i:s') . '</info>');

        try {
            set_time_limit(0);

            $store = $this->storeManager->getStore();

            exec('wget ' . $store->getConfig('process/dump_pro/url'));

            $name = basename($store->getConfig('process/dump_pro/url'));
            exec(
                'gunzip < ' . $name . ' |'
                . ' mysql'
                . ' -h' . $this->deploymentConfig->get('db/connection/sho_pro/host')
                . ' -u' . $this->deploymentConfig->get('db/connection/sho_pro/username')
                . ' -p' . $this->deploymentConfig->get('db/connection/sho_pro/password')
                . ' ' . $this->deploymentConfig->get('db/connection/sho_pro/dbname')
            );

            unlink($name);

            [
                $this->_mapHeader,
                $this->_processedStores
            ] = $this->serializer->unserialize($this->process->getExportData());

            $csvFilePath = $this->saveToCsv('sho_pro');

            $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(ProductModel::ENTITY . '/sho_pro');
            $gzFilePath = $fileDirectory . '/' . 'products_sho_pro.gz';

            //Export CSV to API
            $string = file_get_contents($csvFilePath);
            $gz = gzopen($gzFilePath, 'w9');
            gzwrite($gz, $string);
            gzclose($gz);

            $uploaded = $this->moveFile($store, $gzFilePath);

            if ($uploaded) {
                $output->writeln('U P L O A D E D');
            } else {
                $output->writeln('F A L S E');
            }
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }

        $output->writeln('<info>' . date('Y-m-d H:i:s') . '</info>');
        $output->writeln('');
        $output->writeln('<info>Dump process complete</info>');
        $output->writeln('');
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @param string $gzFilePath
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function moveFile($store, $gzFilePath)
    {
        //get token from admin configuration
        $merchantId = $store->getConfig('process/dump_pro/merchant_id');
        $token = $store->getConfig('process/dump_pro/bearer_token');

        //Assign API Credentials
        $this->apiExport->assignApiCredentials($merchantId, $token, true);

        //Get catalog API Url
        $apiUrl = $this->apiExport->getApiUrl(ProductModel::ENTITY);

        $apiExportResult = $this->apiExport->apiExport($apiUrl, $gzFilePath);

        echo "\n";
        var_dump($apiExportResult);
        echo "\n";

        if ($apiExportResult['result'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * Save CSV for Website
     *
     * @param string $websiteId
     * @return string
     * @throws \Exception
     */
    public function saveToCsv($websiteId)
    {
        $this->_preparedData = [];

        $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(ProductModel::ENTITY . '/' . $websiteId);
        $this->emarsysHelper->checkAndCreateFolder($fileDirectory);

        $name = 'products_' . $websiteId . '.csv';
        $file = $fileDirectory . '/' . $name;

        $fh = fopen($file, 'w');
        $this->file->filePutCsv($fh, $this->_mapHeader, $this->_delimiter, $this->_enclosure);
        $this->_prepareData($fh);
        fclose($fh);

        return $file;
    }

    /**
     * Prepare Data for CSV
     *
     * @param resource $fh
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareData($fh)
    {
        $currentPageNumber = 1;

        $columnCount = count($this->_mapHeader);
        $emptyArray = array_fill(0, $columnCount, "");

        $collection = $this->process->getResourceCollection();
        $collection->setPageSize(self::BATCH_SIZE)
            ->setCurPage($currentPageNumber);

        $lastPageNumber = $collection->getLastPageNumber();

        while ($currentPageNumber <= $lastPageNumber) {
            if ($currentPageNumber != 1) {
                $collection->setCurPage($currentPageNumber);
                $collection->clear();
            }
            foreach ($collection as $product) {
                $data = [];
                $productId = $product->getId();
                $productData = explode(self::EMARSYS_DELIMITER, $product->getParams());
                foreach ($productData as $param) {
                    $item = $this->serializer->unserialize($param);

                    if (!isset($data[$productId])) {
                        $data[$productId] = array_fill(0, count($this->_mapHeader), "");
                    } else {
                        $processed = $data[$productId];
                        $data[$productId] = array_fill(0, count($this->_mapHeader), "");
                        $data[$productId] = $processed + $data[$productId];
                    }

                    $this->prepareDataForCsv($item, $productId, $data);
                }
                ksort($data[$productId]);

                if (count($data[$productId]) < $columnCount) {
                    $data[$productId] = $data[$productId] + $emptyArray;
                }

                $this->file->filePutCsv($fh, $data[$productId], $this->_delimiter, $this->_enclosure);

                if (isset($item['is_simple_parent']) && $item['is_simple_parent']) {
                    $data[$productId][0] = $item['is_simple_parent'];
                    $this->file->filePutCsv($fh, $data[$productId], $this->_delimiter, $this->_enclosure);
                }
            }

            $currentPageNumber++;
        }
        return true;
    }

    /**
     * @param $item
     * @param $productId
     * @param $data
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataForCsv($item, $productId, &$data)
    {
        $map = $this->_processedStores[$item['store']];
        foreach ($item['data'] as $key => $value) {
            if (isset($map[$key])) {
                if (isset($this->_mapHeader[$map[$key]])
                    && (
                        $this->_mapHeader[$map[$key]] == 'price_' . $item['currency_code']
                        || $this->_mapHeader[$map[$key]] == 'msrp_' . $item['currency_code']
                    )) {
                    $rate = 1;
                    if (isset($item['currency_rate'])) {
                        $rate = $item['currency_rate'];
                    } else {
                        $currencyCodeTo = $this->getCurrencyCodeTo($item['store_id']);
                        if ($item['currency_code'] != $currencyCodeTo) {
                            $rate = $this->getRate($item['currency_code'], $currencyCodeTo);
                        }
                    }
                    $value = number_format(
                        $value * $rate,
                        2,
                        '.',
                        ''
                    );
                }
                $data[$productId][$map[$key]] = str_replace(["\n", "\r"], "", $value);
            }
        }
    }

    public function getCurrencyCodeTo($storeId)
    {
        if (!isset($this->currencyCodeTo[$storeId])) {
            $this->currencyCodeTo[$storeId] = $this->storeManager
                ->getStore($storeId)
                ->getBaseCurrency()
                ->getCode();
        }

        return $this->currencyCodeTo[$storeId];
    }

    public function getRate($currencyCode, $currencyCodeTo)
    {
        if (!isset($this->rate[$currencyCode][$currencyCodeTo])) {
            $this->rate[$currencyCode][$currencyCodeTo] = $this->currencyFactory->create()->load($currencyCode)
                ->getAnyRate($currencyCodeTo);
        }

        return $this->rate[$currencyCode][$currencyCodeTo];
    }
}
