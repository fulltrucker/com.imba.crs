<?php

require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Crs_DAO_RevenueSharing extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   */
  static $_tableName = 'imba_contribution_page_revenue_sharing';
  /**
   * static instance to hold the field values
   *
   * @var array
   */
  static $_fields = null;
  /**
   * static instance to hold the keys used in $_fields for each field.
   *
   * @var array
   */
  static $_fieldKeys = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   */
  static $_log = false;
  /**
   *
   * @var int unsigned
   */
  public $id;

  public $contribution_page_id;

  public $region_mode;

  public $chapter_mode;

  public $region_contact_id;

  public $chapter_contact_id;

  /**
   * class constructor
   *
   * @return civicrm_premiums
   */
  function __construct()
  {
    $this->__table = 'civicrm_contribution_page_revenue_sharing';
    parent::__construct();
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Revenue Sharing ID') ,
          'required' => true,
        ) ,
        'contribution_page_id' => array(
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution Page ID') ,
          'required' => true,
        ),
        'region_mode' => array(
          'name' => 'region_mode',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Region Mode') ,
          'required' => true,
        ),
        'chapter_mode' => array(
          'name' => 'chapter_mode',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Chapter Mode') ,
          'required' => true,
        ),
        'region_contact_id' => array(
          'name' => 'region_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Region') ,
          'required' => false,
        ),
        'chapter_contact_id' => array(
          'name' => 'chapter_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Chapter') ,
          'required' => false,
        ),
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the arary key used for that
   * field in self::$_fields.
   *
   * @return array
   */
  static function &fieldKeys()
  {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'id',
        'contribution_page_id' => 'contribution_page_id',
        'region_mode' => 'region_mode',
        'chapter_mode' => 'chapter_mode',
        'region_contact_id' => 'region_contact_id',
        'chapter_contact_id' => 'chapter_contact_id',
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * Returns the names of this table
   *
   * @return string
   */
  static function getTableName()
  {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }
  /**
   * Returns if this table needs to be logged
   *
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &import($prefix = false)
  {
    return self::$_import;
  }
  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = false)
  {
    return self::$_export;
  }
}
