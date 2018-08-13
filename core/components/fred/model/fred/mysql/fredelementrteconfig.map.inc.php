<?php
/**
 * @package fred
 */
$xpdo_meta_map['FredElementRTEConfig']= array (
  'package' => 'fred',
  'version' => '0.1',
  'table' => 'fred_element_rte_configs',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'name' => NULL,
    'description' => '',
    'theme' => NULL,
    'data' => '',
  ),
  'fieldMeta' => 
  array (
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
    ),
    'description' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'theme' => 
    array (
      'dbtype' => 'int',
      'attributes' => 'unsigned',
      'precision' => '10',
      'phptype' => 'integer',
      'null' => false,
    ),
    'data' => 
    array (
      'dbtype' => 'mediumtext',
      'phptype' => 'json',
      'null' => false,
      'default' => '',
    ),
  ),
  'aggregates' => 
  array (
    'Theme' => 
    array (
      'class' => 'FredTheme',
      'local' => 'theme',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'local',
    ),
  ),
);
