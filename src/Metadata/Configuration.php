<?php

namespace DoctrineExtensionTree\Metadata;

/**
 * Class Configuration
 */
class Configuration
{

    const NODE_IDENTIFIER_GETTING_METHOD_NAME = 'nigmn';

    const PARENT_NODE_HOLDING_PROPERTY_NAME = 'pnhpn';

    const SIBLING_ORDER_POSITION_HOLDING_PROPERTY_NAME = 'sophpn';

    const PATH_NODE_PREFIX_HOLDING_PROPERTY_NAME = 'pnphpn';

    const PATH_NODE_PREFIX_SEPARATOR = 'pnps';

    const PATH_NODE_SUFFIX_HOLDING_PROPERTY_NAME = 'pnshpn';

    const PATH_NODE_SUFFIX_SEPARATOR = 'pnss';

    const READ_ONLY_ROOT_NODE_HOLDING_PROPERTY_NAME = 'rornhpn';

    const READ_ONLY_BRANCH_DEPTH_HOLDING_PROPERTY_NAME = 'robdhpn';

    const READ_ONLY_PATH_SLICE_HOLDING_PROPERTY_NAME_LIST = 'ropshpnl';

    const PATH_SLICE_LENGTH = 'psl';

    const READ_ONLY_PATH_HASH_HOLDING_PROPERTY_NAME = 'rophhpn';

    const READ_ONLY_PARENT_PATH_HASH_HOLDING_PROPERTY_NAME = 'ropphhpn';

    /**
     * @var bool
     */
    private $changed;

    /**
     * @var string|null
     */
    protected $nodeIdentifierGettingMethodName;

    /**
     * @var string
     */
    protected $parentNodeHoldingPropertyName;

    /**
     * @var string|null
     */
    protected $siblingOrderPositionHoldingPropertyName;

    /**
     * @var string|null
     */
    protected $pathNodePrefixHoldingPropertyName;

    /**
     * @var string
     */
    protected $pathNodePrefixSeparator;

    /**
     * @var string|null
     */
    protected $pathNodeSuffixHoldingPropertyName;

    /**
     * @var string
     */
    protected $pathNodeSuffixSeparator;

    /**
     * @var string|null
     */
    protected $readOnlyRootNodeHoldingPropertyName;

    /**
     * @var string|null
     */
    protected $readOnlyBranchDepthHoldingPropertyName;

    /**
     * @var array|string[]
     */
    protected $readOnlyPathSliceHoldingPropertyNameList;

    /**
     * @var int|null
     */
    protected $pathSliceLength;

    /**
     * @var string|null
     */
    protected $readOnlyPathHashHoldingPropertyName;

    /**
     * @var string|null
     */
    protected $readOnlyParentPathHashHoldingPropertyName;

    /**
     * Configuration constructor
     */
    final public function __construct()
    {
        $this->changed = true;
        $this->nodeIdentifierGettingMethodName = null;
        $this->parentNodeHoldingPropertyName = 'parent';
        $this->siblingOrderPositionHoldingPropertyName = null;
        $this->pathNodePrefixHoldingPropertyName = null;
        $this->pathNodePrefixSeparator = '_';
        $this->pathNodeSuffixHoldingPropertyName = null;
        $this->pathNodeSuffixSeparator = '_';
        $this->readOnlyRootNodeHoldingPropertyName = null;
        $this->readOnlyBranchDepthHoldingPropertyName = null;
        $this->readOnlyPathSliceHoldingPropertyNameList = [];
        $this->pathSliceLength = null;
        $this->readOnlyPathHashHoldingPropertyName = null;
        $this->readOnlyParentPathHashHoldingPropertyName = null;
        $this->initialize();
    }

    /**
     * @return array
     * @internal
     */
    public function toArray()
    {
        $configuration = [];
        $configuration[self::NODE_IDENTIFIER_GETTING_METHOD_NAME] = $this->nodeIdentifierGettingMethodName;
        $configuration[self::PARENT_NODE_HOLDING_PROPERTY_NAME] = $this->parentNodeHoldingPropertyName;
        $configuration[self::SIBLING_ORDER_POSITION_HOLDING_PROPERTY_NAME] = $this->siblingOrderPositionHoldingPropertyName;
        $configuration[self::PATH_NODE_PREFIX_HOLDING_PROPERTY_NAME] = $this->pathNodePrefixHoldingPropertyName;
        $configuration[self::PATH_NODE_PREFIX_SEPARATOR] = $this->pathNodePrefixSeparator;
        $configuration[self::PATH_NODE_SUFFIX_HOLDING_PROPERTY_NAME] = $this->pathNodeSuffixHoldingPropertyName;
        $configuration[self::PATH_NODE_SUFFIX_SEPARATOR] = $this->pathNodeSuffixSeparator;
        $configuration[self::READ_ONLY_ROOT_NODE_HOLDING_PROPERTY_NAME] = $this->readOnlyRootNodeHoldingPropertyName;
        $configuration[self::READ_ONLY_BRANCH_DEPTH_HOLDING_PROPERTY_NAME] = $this->readOnlyBranchDepthHoldingPropertyName;
        $configuration[self::READ_ONLY_PATH_SLICE_HOLDING_PROPERTY_NAME_LIST] = $this->readOnlyPathSliceHoldingPropertyNameList;
        $configuration[self::PATH_SLICE_LENGTH] = $this->pathSliceLength;
        $configuration[self::READ_ONLY_PATH_HASH_HOLDING_PROPERTY_NAME] = $this->readOnlyPathHashHoldingPropertyName;
        $configuration[self::READ_ONLY_PARENT_PATH_HASH_HOLDING_PROPERTY_NAME] = $this->readOnlyParentPathHashHoldingPropertyName;
        return $configuration;
    }

    /**
     * @param array $configuration
     * @return static
     * @internal
     */
    public function fromArray(array $configuration)
    {
        if (array_key_exists(self::NODE_IDENTIFIER_GETTING_METHOD_NAME, $configuration)) {
            $this->nodeIdentifierGettingMethodName = $configuration[self::NODE_IDENTIFIER_GETTING_METHOD_NAME];
        }
        if (array_key_exists(self::PARENT_NODE_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->parentNodeHoldingPropertyName = $configuration[self::PARENT_NODE_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::SIBLING_ORDER_POSITION_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->siblingOrderPositionHoldingPropertyName = $configuration[self::SIBLING_ORDER_POSITION_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::PATH_NODE_PREFIX_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->pathNodePrefixHoldingPropertyName = $configuration[self::PATH_NODE_PREFIX_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::PATH_NODE_PREFIX_SEPARATOR, $configuration)) {
            $this->pathNodePrefixSeparator = $configuration[self::PATH_NODE_PREFIX_SEPARATOR];
        }
        if (array_key_exists(self::PATH_NODE_SUFFIX_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->pathNodeSuffixHoldingPropertyName = $configuration[self::PATH_NODE_SUFFIX_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::PATH_NODE_SUFFIX_SEPARATOR, $configuration)) {
            $this->pathNodeSuffixSeparator = $configuration[self::PATH_NODE_SUFFIX_SEPARATOR];
        }
        if (array_key_exists(self::READ_ONLY_ROOT_NODE_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->readOnlyRootNodeHoldingPropertyName = $configuration[self::READ_ONLY_ROOT_NODE_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::READ_ONLY_BRANCH_DEPTH_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->readOnlyBranchDepthHoldingPropertyName = $configuration[self::READ_ONLY_BRANCH_DEPTH_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::READ_ONLY_PATH_SLICE_HOLDING_PROPERTY_NAME_LIST, $configuration)) {
            $this->readOnlyPathSliceHoldingPropertyNameList = $configuration[self::READ_ONLY_PATH_SLICE_HOLDING_PROPERTY_NAME_LIST];
        }
        if (array_key_exists(self::PATH_SLICE_LENGTH, $configuration)) {
            $this->pathSliceLength = $configuration[self::PATH_SLICE_LENGTH];
        }
        if (array_key_exists(self::READ_ONLY_PATH_HASH_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->readOnlyPathHashHoldingPropertyName = $configuration[self::READ_ONLY_PATH_HASH_HOLDING_PROPERTY_NAME];
        }
        if (array_key_exists(self::READ_ONLY_PARENT_PATH_HASH_HOLDING_PROPERTY_NAME, $configuration)) {
            $this->readOnlyParentPathHashHoldingPropertyName = $configuration[self::READ_ONLY_PARENT_PATH_HASH_HOLDING_PROPERTY_NAME];
        }
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNodeIdentifierGettingMethodName()
    {
        $this->assertConfigurationIsValid();
        return $this->nodeIdentifierGettingMethodName;
    }

    /**
     * @param string|null $nodeIdentifierGettingMethodName
     * @return static
     */
    public function setNodeIdentifierGettingMethodName($nodeIdentifierGettingMethodName)
    {
        $this->nodeIdentifierGettingMethodName = $nodeIdentifierGettingMethodName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentNodeHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->parentNodeHoldingPropertyName;
    }

    /**
     * @param string $parentNodeHoldingPropertyName
     * @return static
     */
    public function setParentNodeHoldingPropertyName($parentNodeHoldingPropertyName)
    {
        $this->parentNodeHoldingPropertyName = $parentNodeHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSiblingOrderPositionHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->siblingOrderPositionHoldingPropertyName;
    }

    /**
     * @param string|null $siblingOrderPositionHoldingPropertyName
     * @return static
     */
    public function setSiblingOrderPositionHoldingPropertyName($siblingOrderPositionHoldingPropertyName)
    {
        $this->siblingOrderPositionHoldingPropertyName = $siblingOrderPositionHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPathNodePrefixHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->pathNodePrefixHoldingPropertyName;
    }

    /**
     * @param string|null $pathNodePrefixHoldingPropertyName
     * @return static
     */
    public function setPathNodePrefixHoldingPropertyName($pathNodePrefixHoldingPropertyName)
    {
        $this->pathNodePrefixHoldingPropertyName = $pathNodePrefixHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getPathNodePrefixSeparator()
    {
        $this->assertConfigurationIsValid();
        return $this->pathNodePrefixSeparator;
    }

    /**
     * @param string $pathNodePrefixSeparator
     * @return static
     */
    public function setPathNodePrefixSeparator($pathNodePrefixSeparator)
    {
        $this->pathNodePrefixSeparator = $pathNodePrefixSeparator;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPathNodeSuffixHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->pathNodeSuffixHoldingPropertyName;
    }

    /**
     * @param string|null $pathNodeSuffixHoldingPropertyName
     * @return static
     */
    public function setPathNodeSuffixHoldingPropertyName($pathNodeSuffixHoldingPropertyName)
    {
        $this->pathNodeSuffixHoldingPropertyName = $pathNodeSuffixHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getPathNodeSuffixSeparator()
    {
        $this->assertConfigurationIsValid();
        return $this->pathNodeSuffixSeparator;
    }

    /**
     * @param string $pathNodeSuffixSeparator
     * @return static
     */
    public function setPathNodeSuffixSeparator($pathNodeSuffixSeparator)
    {
        $this->pathNodeSuffixSeparator = $pathNodeSuffixSeparator;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReadOnlyRootNodeHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->readOnlyRootNodeHoldingPropertyName;
    }

    /**
     * @param string|null $readOnlyRootNodeHoldingPropertyName
     * @return static
     */
    public function setReadOnlyRootNodeHoldingPropertyName($readOnlyRootNodeHoldingPropertyName)
    {
        $this->readOnlyRootNodeHoldingPropertyName = $readOnlyRootNodeHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReadOnlyBranchDepthHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->readOnlyBranchDepthHoldingPropertyName;
    }

    /**
     * @param string|null $readOnlyBranchDepthHoldingPropertyName
     * @return static
     */
    public function setReadOnlyBranchDepthHoldingPropertyName($readOnlyBranchDepthHoldingPropertyName)
    {
        $this->readOnlyBranchDepthHoldingPropertyName = $readOnlyBranchDepthHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getReadOnlyPathSliceHoldingPropertyNameList()
    {
        $this->assertConfigurationIsValid();
        return $this->readOnlyPathSliceHoldingPropertyNameList;
    }

    /**
     * @param string $readOnlyPathSliceHoldingPropertyName
     * @return static
     */
    public function addReadOnlyPathSliceHoldingPropertyName($readOnlyPathSliceHoldingPropertyName)
    {
        $this->readOnlyPathSliceHoldingPropertyNameList[] = $readOnlyPathSliceHoldingPropertyName;
        $this->readOnlyPathSliceHoldingPropertyNameList = array_unique($this->readOnlyPathSliceHoldingPropertyNameList);
        sort($this->readOnlyPathSliceHoldingPropertyNameList);
        $this->changed = true;
        return $this;
    }

    /**
     * @return static
     */
    public function clearReadOnlyPathSliceHoldingPropertyNameList()
    {
        $this->readOnlyPathSliceHoldingPropertyNameList = [];
        $this->changed = true;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPathSliceLength()
    {
        $this->assertConfigurationIsValid();
        return $this->pathSliceLength;
    }

    /**
     * @param int|null $pathSliceLength
     * @return Configuration
     */
    public function setPathSliceLength($pathSliceLength)
    {
        $this->pathSliceLength = $pathSliceLength;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReadOnlyPathHashHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->readOnlyPathHashHoldingPropertyName;
    }

    /**
     * @param string|null $readOnlyPathHashHoldingPropertyName
     * @return static
     */
    public function setReadOnlyPathHashHoldingPropertyName($readOnlyPathHashHoldingPropertyName)
    {
        $this->readOnlyPathHashHoldingPropertyName = $readOnlyPathHashHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReadOnlyParentPathHashHoldingPropertyName()
    {
        $this->assertConfigurationIsValid();
        return $this->readOnlyParentPathHashHoldingPropertyName;
    }

    /**
     * @param string|null $readOnlyParentPathHashHoldingPropertyName
     * @return static
     */
    public function setReadOnlyParentPathHashHoldingPropertyName($readOnlyParentPathHashHoldingPropertyName)
    {
        $this->readOnlyParentPathHashHoldingPropertyName = $readOnlyParentPathHashHoldingPropertyName;
        $this->changed = true;
        return $this;
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // do nothing
    }

    /**
     * 
     */
    protected function assertConfigurationIsValid()
    {
        if (!$this->changed) {
            return;
        }
        // todo: check if at least on path slice
        // todo: check if path slice length is valid
        // todo: node path prefix and node path suffix can not be the same
        $this->changed = false;
    }

}
