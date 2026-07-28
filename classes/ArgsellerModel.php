<?php
/**
 * 2026 ARGSEGURIDAD
 * ObjectModel for Argsellers
 */

class ArgsellerModel extends ObjectModel
{
    public $id_seller;
    public $name;
    public $role;
    public $phone;
    public $email;
    public $image;
    public $active;
    public $position;

    public static $definition = array(
        'table' => 'argsellers',
        'primary' => 'id_seller',
        'multilang' => false,
        'fields' => array(
            'name' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'role' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'phone' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 32),
            'email' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255),
            'image' => array('type' => ObjectModel::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'active' => array('type' => ObjectModel::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'position' => array('type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedInt'),
        ),
    );
}
