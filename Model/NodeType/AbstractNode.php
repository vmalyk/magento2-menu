<?php
/**
 * Snowdog
 *
 * @author      Paweł Pisarek <pawel.pisarek@snow.dog>.
 * @category
 * @package
 * @copyright   Copyright Snowdog (http://snow.dog)
 */

namespace Snowdog\Menu\Model\NodeType;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Snowdog\Menu\Api\Data\NodeTypeInterface;

abstract class AbstractNode implements NodeTypeInterface
{
    /**
     * Name of the resource model
     *
     * @var string
     */
    private $_resourceName;
    /**
     * Resource model instance
     *
     * @var \Snowdog\Menu\Model\ResourceModel\NodeType\AbstractNode
     */
    private $_resource;
    /**
     * @var \Magento\Framework\Profiler
     */
    protected $profiler;

    /**
     * AbstractNode constructor.
     */
    public function __construct(
        \Magento\Framework\Profiler $profiler
    ) {
        $this->_construct();
        $this->profiler = $profiler;
    }

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function fetchConfigData()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->_resourceName;
    }

    /**
     * @inheritDoc
     */
    public function fetchData(array $nodes, $storeId)
    {
        $this->profiler->start(__METHOD__);

        $localNodes = [];

        foreach ($nodes as $node) {
            $localNodes[$node->getId()] = $node;
        }

        $this->profiler->stop(__METHOD__);

        return $localNodes;
    }

    /**
     * Standard model initialization
     *
     * @param string $resourceModel
     * @return void
     */
    protected function _init($resourceModel)
    {
        $this->_resourceName = $resourceModel;
    }

    /**
     * Get resource instance
     *
     * @throws LocalizedException
     * @return \Snowdog\Menu\Model\ResourceModel\NodeType\AbstractNode
     */
    protected function getResource()
    {
        if (empty($this->_resourceName) && empty($this->_resource)) {
            throw new LocalizedException(
                new Phrase('The resource isn\'t set.')
            );
        }

        return $this->_resource ?: ObjectManager::getInstance()->get($this->_resourceName);
    }
}
