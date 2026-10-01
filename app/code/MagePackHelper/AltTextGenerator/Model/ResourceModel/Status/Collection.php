<?php

namespace MagePackHelper\AltTextGenerator\Model\ResourceModel\Status;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagePackHelper\AltTextGenerator\Model\Status as StatusModel;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status as StatusResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'mage_pack_alt_text_status_collection';
    protected $_eventObject = 'mage_pack_alt_text_status_collection';

    protected function _construct()
    {
        $this->_init(StatusModel::class, StatusResource::class);
    }
}
