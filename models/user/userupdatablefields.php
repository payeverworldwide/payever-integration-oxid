<?php

/**
 * @inheritdoc
 */
class userupdatablefields extends userupdatablefields_parent
{
    /**
     * Return list of fields which could be updated by shop customer.
     *
     * @return array
     */
    public function getUpdatableFields()
    {
        return array_merge(parent::getUpdatableFields(), ['OXEXTERNALID', 'OXVATID']);
    }
}
