<?php

namespace Kengineering\Sonar\Objects;

/**
 * @property int $default_vendor_id
 * @property string $device_type
 * @property bool $enabled
 * @property bool $generic
 * @property string $icon
 * @property int $inventory_model_category_id
 * @property string $lte_sim_type
 * @property int $manufacturer_id
 * @property string $model_name
 * @property string $name
 * @property int $network_monitoring_template_id
 * @property int $port
 * @property string $protocol
 * @property InventoryItem $inventory_item
 * @property InventoryModelField $inventory_model_field
 */
class InventoryModelFieldData extends SonarObject
{
    const PROPERTIES = [
        'id',
        'sonar_unique_id',
        'created_at',
        'updated_at',
        '_version',
        'inventory_item_id',
        'inventory_model_field_id',
        'value',
    ];

    const RELATED_OBJECTS = [
        'inventory_item' => 'one',
        'inventory_model_field' => 'one',
    ];

    const OBJECT_MULTIPLE_NAME = 'inventory_model_field_data';

}
