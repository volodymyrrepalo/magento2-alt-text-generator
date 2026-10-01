<?php

namespace MagePackHelper\AltTextGenerator\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Status extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mage_pack_alt_text_status', 'entity_id');
    }
}
