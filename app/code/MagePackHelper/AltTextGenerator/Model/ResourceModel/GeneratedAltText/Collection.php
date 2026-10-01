<?php

namespace MagePackHelper\AltTextGenerator\Model\ResourceModel\GeneratedAltText;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagePackHelper\AltTextGenerator\Model\GeneratedAltText;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\GeneratedAltText as GeneratedAltTextResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'mage_pack_alt_text_generated_collection';
    protected $_eventObject = 'mage_pack_alt_text_generated_collection';

    protected function _construct()
    {
        $this->_init(GeneratedAltText::class, GeneratedAltTextResource::class);
    }
}
