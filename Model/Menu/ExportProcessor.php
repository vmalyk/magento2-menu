<?php

namespace Snowdog\Menu\Model\Menu;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Snowdog\Menu\Api\Data\MenuInterface;
use Snowdog\Menu\Api\Data\NodeInterface;
use Snowdog\Menu\Api\MenuRepositoryInterface;
use Snowdog\Menu\Api\NodeRepositoryInterface;
use Snowdog\Menu\Model\ResourceModel\Menu as MenuResource;

class ExportProcessor
{
    const EXPORT_DIR = 'importexport';
    const STORES_CSV_FIELD = 'stores';
    const NODES_CSV_FIELD = 'nodes';

    const MENU_EXCLUDED_FIELDS = [
        MenuInterface::MENU_ID
    ];

    const MENU_RELATION_TABLES_FIELDS = [
        self::STORES_CSV_FIELD,
        self::NODES_CSV_FIELD
    ];

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MenuRepositoryInterface
     */
    private $menuRepository;

    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var MenuResource
     */
    private $menuResource;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Filesystem $filesystem,
        SerializerInterface $serializer,
        MenuRepositoryInterface $menuRepository,
        NodeRepositoryInterface $nodeRepository,
        MenuResource $menuResource
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->serializer = $serializer;
        $this->menuRepository = $menuRepository;
        $this->nodeRepository = $nodeRepository;
        $this->menuResource = $menuResource;
    }

    /**
     * @param int $menuId
     * @return array
     */
    public function getExportFileDownloadContent($menuId)
    {
        $data = $this->getExportData($menuId);
        return $this->generateCsvDownloadFile($data[MenuInterface::IDENTIFIER], $data);
    }

    /**
     * @param string $fileId
     * @param array|null $csvHeaders
     * @return array
     */
    public function generateCsvDownloadFile($fileId, array $data, $csvHeaders = null)
    {
        $this->directory->create(self::EXPORT_DIR);

        $file = $this->getDownloadFile($fileId);
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $stream->writeCsv($csvHeaders ?: $this->getMenuFields());
        $stream->writeCsv($data);

        $stream->unlock();
        $stream->close();

        return ['type' => 'filename', 'value' => $file, 'rm' => true];
    }

    /**
     * @param int $menuId
     * @return array
     */
    private function getExportData($menuId)
    {
        $menu = $this->menuRepository->getById($menuId);
        $stores = $menu->getStores();
        $data = $menu->getData();
        $nodes = $this->getMenuNodeList($menuId);

        $data[self::STORES_CSV_FIELD] = implode(',', $stores);
        $data[self::NODES_CSV_FIELD] = $nodes ? $this->serializer->serialize($nodes) : null;

        unset($data[MenuInterface::MENU_ID]);

        return $data;
    }

    /**
     * @param int $menuId
     * @return array
     */
    private function getMenuNodeList($menuId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(NodeInterface::MENU_ID, $menuId)
            ->create();

        $nodes = $this->nodeRepository->getList($searchCriteria)->getItems();
        $nodesData = [];

        foreach ($nodes as $key => $node) {
            $nodesData[$key] = $node->getData();
            unset($nodesData[$key][NodeInterface::MENU_ID]);
        }

        return $nodesData;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @return array
     */
    private function getMenuFields()
    {
        $fields = [];
        $excludedFields = array_flip(self::MENU_EXCLUDED_FIELDS);
        
        foreach ($this->menuResource->getFields() as $field => $config) {
            if (!isset($excludedFields[$field])) {
                $fields[] = $field;
            }
        }

        return array_merge($fields, self::MENU_RELATION_TABLES_FIELDS);
    }

    /**
     * @param string $fileId
     * @return string
     */
    private function getDownloadFile($fileId)
    {
        return self::EXPORT_DIR . DIRECTORY_SEPARATOR . $fileId . '-' . hash('sha256', microtime()) . '.csv';
    }
}