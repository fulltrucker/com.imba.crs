<?php

require_once 'crs.civix.php';

define('CRS_REGION_NONE', 0);       // leave blank/null
define('CRS_REGION_SELECTED', 1);   // use the selected region
define('CRS_REGION_USER', 2);       // let the user choose
define('CRS_REGION_CHAPTER', 3);    // use the region of the selected chapter
define('CRS_REGION_POSTAL', 4);     // lookup the region using postal code

define('CRS_CHAPTER_NONE', 0);      // leave blank/null
define('CRS_CHAPTER_SELECTED', 1);  // use the selected chapter

define('CRS_DEFAULT_REGION', 275824);

/*
  The following helper functions facilitate the conversion of the current
  alphanumeric custom fields into contact references, and back.
  This allows me to fully develope and test the extension without having
  the new contact reference custom fields created yet.
  They are also used by the civitracker.module simulation.
  These will be removed once the new fields are available and civitracker
  is fully deprecated.
*/

// this one must be kept
function crs_contact_to_name($id, $default = '') {
    $name = $default;
    
    if (!is_null($id)) {
      $api = new civicrm_api3();
      $api->Contact->GetSingle(array('id' => $id, 'return' => 'organization_name,nick_name'));
      if (empty($api->result->is_error)) {
        $name = $api->result->organization_name;
        if (!empty($api->result->nick_name))
          $name .= " ({$api->result->nick_name})";
      }
    }
    return $name;
}
function crs_chapter_name_to_contact($name) {
  if (!empty($name) && !is_numeric($name)) {

    $api = new civicrm_api3();
    if ($i = strpos($name, '('))
      $name = substr($name, 0, $i);
    $api->Contact->GetValue(array('contact_sub_type' => 'Chapter',
                    'organization_name' => trim($name), 'return' => 'id'));
    $name = is_string($api->result) ? $api->result : null;
  }
  return $name;
}
function crs_region_name_to_contact($name) {
  if (!empty($name) && !is_numeric($name)) {

    $api = new civicrm_api3();
    $api->Contact->GetValue(array('contact_sub_type' => 'Region',
                  'organization_name' => trim($name), 'return' => 'id'));
    $name = is_string($api->result) ? $api->result : null;
  }
  return $name;
}

/*
  This is a dummy form class that will get passed to the
  civitracker moudule in order to pre-populate the table
  contribution_page_revenue_sharing this extension creates.
*/
class fake_civitracker_form {
  private $_id;

  function __construct($contribution_page_id = 0) {
    $this->_id = $contribution_page_id;
  }

  function getVar() {
    return $this->_id;
  }

  function setDefaults() {}

  function assign() {}

  function setID($contribution_page_id) {
    $this->_id = $contribution_page_id;
  }

  // this function simulates a call to the civitracker module and
  // returns the region and chapter assigned to the $_GET variables
  function civitracker() {

    civitracker_civicrm_buildForm('CRM_Contribute_Form_Contribution_Main', $this);

    $result = array(
        'region_76' => $_GET['custom_76'],
        'chapter_77' => $_GET['custom_77']
    );

    unset($_GET['custom_76'], $_GET['custom_77'], $_GET['custom_79']);

    return $result;
  }
}

function crs_civicrm_buildForm($formName, &$form) {
  if (($formName == 'CRM_Contribute_Form_Contribution_Main' ||
    $formName == 'CRM_Contribute_Form_Contribution_Confirm' ||
    $formName == 'CRM_Contribute_Form_Contribution_ThankYou')
  ) {

    $dao = new CRM_Crs_DAO_RevenueSharing();
    $dao->contribution_page_id = $form->_id;
    $dao->find(TRUE);

    if ($dao->id) {
      $settings = array();
      CRM_Core_DAO::storeValues($dao, $settings);

      if ($settings['region_mode'] == CRS_REGION_USER) {

        if ($formName == 'CRM_Contribute_Form_Contribution_Main') {

          $options = array();
          $dao = CRM_Core_DAO::executeQuery("SELECT id,organization_name FROM civicrm_contact WHERE contact_sub_type='Region' ORDER BY organization_name ASC");
          while ($dao->fetch()) {
            $options[$dao->id] = $dao->organization_name;
          }
          $form->addSelect('region_contact_id', array('label' => 'Region', 'options' => $options), true);
          $form->setDefaults(array('region_contact_id' => CRS_DEFAULT_REGION));

          // build zipcode to region lookup table
          $table = array();
          
          $dao = CRM_Core_DAO::executeQuery('SELECT postal_code, region_contact_id FROM civicrm_regionfields_data');
          while ($dao->fetch()) {
            if (empty($table[$dao->region_contact_id])) {
              $table[$dao->region_contact_id] = array(
                'id' => $dao->region_contact_id,
                'codes' => array()
              );
            }
            $table[$dao->region_contact_id]['codes'][] = $dao->postal_code;
          }
          $form->assign('lookup_table', json_encode(array_values($table)));

          if (!empty($_SESSION['crs_fields']))
            $form->setDefaults(array('region_contact_id' => $_SESSION['crs_fields']['region_contact_id']));
        }
        else {
          $form->add('text', 'region_contact_id', 'Region');
          $form->setDefaults(array('region_contact_id' => crs_contact_to_name($_SESSION['crs_fields']['region_contact_id'])));
        }
      }
    }
  }
}

function crs_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {

  if ($formName == 'CRM_Contribute_Form_Contribution_Main') {
    $_SESSION['crs_fields'] = $fields;

    if (isset($_SERVER['USER']) && ($_SERVER['USER'] == 'davidrjr')) {
      // the following selects the latest contribution made for this page and calls the post hook on it
      // allows testing without creating any new contributions, since that fails on my local server
      $api = new civicrm_api3();

      $api->Contribution->GetSingle(array('contribution_page_id' => $form->_id, 'options' => array('limit' => 1, 'sort' => 'receive_date DESC')));
      watchdog('crs', print_r($api->result, true));
    }

    crs_civicrm_post('create', 'Contribution', $api->result->id, $api->result);
  }

  return true;
}
/*
function crs_civicrm_pre($op, $objectName, $id, $params) {
  watchdog('crs', "pre $op $objectName ($id)", $params);
}

function crs_civicrm_custom($op, $groupID, $entityID, $params) {
  watchdog('crs', "$op group $groupID, entity $entityID", $params);
}
*/

/**
 * Implements hook_civicrm_post().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function crs_civicrm_post($op, $objectName, $objectId, &$objectRef) {

  //watchdog('crs', "post $op $objectName ($objectId)", $objectRef);

  if (($op == 'create') && ($objectName == 'Contribution') && !empty($_SESSION['crs_fields'])) {

    // we only care about contributions made from a contribution page
    if (!$objectRef->contribution_page_id)
      return;

    // get revenue sharing settings for the contribution page
    $dao = new CRM_Crs_DAO_RevenueSharing();
    $dao->contribution_page_id = $objectRef->contribution_page_id;
    $dao->find(TRUE);

    if ($dao->id) {
      $settings = array();
      CRM_Core_DAO::storeValues($dao, $settings);
    }
    else
      $settings = array(
        'region_mode' => CRS_REGION_NONE,
        'chapter_mode' => CRS_CHAPTER_NONE,
        'region_contact_id' => null,
        'chapter_contact_id' => null,
      );

    $api = new civicrm_api3();

    // assign region
    switch ($settings['region_mode']) {

      case CRS_REGION_NONE:
        $region_contact_id = null;
        break;

      case CRS_REGION_SELECTED:
        $region_contact_id = $settings['region_contact_id'];
        break;

      case CRS_REGION_USER:
        $region_contact_id = $_SESSION['crs_fields']['region_contact_id'];
        break;

      case CRS_REGION_CHAPTER:
        // use the region of the selected chapter
        if ($api->Contact->GetValue(array('id' => $settings['chapter_contact_id'], 'return' => 'custom_241')))
          $region_contact_id = $api->result;
        else
          $region_contact_id = null;
        break;

      case CRS_REGION_POSTAL:
        // fields postal_code and billing_postal_code get a hyphenated suffix added
        // I don't know where that comes from, so search through the keys to find them
        $primary = $billing = false;
        foreach($_SESSION['crs_fields'] as $k => $v) {
          if (!$primary && strpos($k, 'postal_code') === 0)
            $primary = $v;
          if (!$billing && strpos($k, 'billing_postal_code') === 0)
            $billing = $v;
        }
        $query = 'SELECT region_contact_id FROM civicrm_regionfields_data WHERE postal_code=';
        // use billing postal code first
        if ($billing)
          $region_contact_id = CRM_Core_DAO::singleValueQuery($query . $billing);
        // fall back to primary not found
        if (!$region_contact_id)
          $region_contact_id = CRM_Core_DAO::singleValueQuery($query . $primary);
        break;
    }
    $api->CustomValue->Create(array('entity_id' => $objectId, 'custom_277' => $region_contact_id));

    // assign chapter
    switch ($settings['chapter_mode']) {
      
      case CRS_CHAPTER_NONE:
        $chapter_contact_id = null;
        break;

      case CRS_CHAPTER_SELECTED:
        $chapter_contact_id = $settings['chapter_contact_id'];
        break;
    }
    $api->CustomValue->Create(array('entity_id' => $objectId, 'custom_278' => $chapter_contact_id));

    if (isset($_SERVER['USER']) && ($_SERVER['USER'] == 'davidrjr'))
      return;

    unset($_SESSION['crs_fields']);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function crs_civicrm_config(&$config) {
  _crs_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function crs_civicrm_xmlMenu(&$files) {
  _crs_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function crs_civicrm_install() {

  if (!function_exists('civitracker_civicrm_buildForm'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/' . drupal_get_path('module', 'civitracker') . '/civitracker.module');

  $dummy = new fake_civitracker_form();

  CRM_Core_DAO::executeQuery("CREATE TABLE IF NOT EXISTS `contribution_page_revenue_sharing` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `contribution_page_id` int(10) unsigned NOT NULL,
    `region_mode` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `chapter_mode` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `region_contact_id` int(10) unsigned DEFAULT NULL,
    `chapter_contact_id` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `contribution_page_id` (`contribution_page_id`),
    KEY `region_xxx` (`region_contact_id`),
    KEY `chapter_yyy` (`chapter_contact_id`),
    CONSTRAINT `civicrm_contribution_page_revenue_sharing_ibfk_1` FOREIGN KEY (`region_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
    CONSTRAINT `civicrm_contribution_page_revenue_sharing_ibfk_2` FOREIGN KEY (`chapter_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
    CONSTRAINT `civicrm_contribution_page_revenue_sharing_ibfk_3` FOREIGN KEY (`contribution_page_id`) REFERENCES `civicrm_contribution_page` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

  $chapters = array();
  $dao = CRM_Core_DAO::executeQuery("SELECT m.contact_id AS id,
                                    c.organization_name, c.nick_name
                                    FROM civicrm_membership m
                                    INNER JOIN civicrm_contact c ON m.contact_id = c.id
                                    WHERE m.membership_type_id IN (7,9,11) AND m.status_id IN (3,2,1)
                                    ORDER BY c.organization_name asc");
  while ($dao->fetch()) {
    $name = $dao->organization_name;
    if ($dao->nick_name)
      $name .= " ({$dao->nick_name})";
    $chapters[$name] = $dao->id;
  }

  $regions = array();
  $dao = CRM_Core_DAO::executeQuery("SELECT id,organization_name FROM civicrm_contact WHERE contact_sub_type='Region' ORDER BY organization_name ASC");
  while ($dao->fetch()){
    $name = $dao->organization_name;
    $regions[$name] = $dao->id;
  }

  $query = 'INSERT INTO contribution_page_revenue_sharing
            (contribution_page_id,region_mode,chapter_mode,region_contact_id,chapter_contact_id)
            VALUES ';

  $dao = CRM_Core_DAO::executeQuery('SELECT id as contribution_page_id FROM civicrm_contribution_page');
  while ($dao->fetch()) {
    $id = $dao->contribution_page_id;
    $dummy->setID($id);
    $names = $dummy->civitracker();
    $region = !empty($regions[$names['region_76']]) ? $regions[$names['region_76']] : 'null';
    $rm = ($region != 'null') ? CRS_REGION_SELECTED : CRS_REGION_POSTAL;
    $chapter = !empty($chapters[$names['chapter_77']]) ? $chapters[$names['chapter_77']] : 'null';
    $cm = ($chapter != 'null') ? CRS_CHAPTER_SELECTED : CRS_CHAPTER_NONE;
    $query .= "($id,$rm,$cm,$region,$chapter),";
  }
  CRM_Core_DAO::executeQuery(substr($query, 0, -1));

  foreach($regions as $name => $id) {
    $name = CRM_Core_DAO::escapeString($name);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_value_revenue_sharing_11 SET contribution_region_277='$id' WHERE region_76='$name'");
  }

  foreach($chapters as $name => $id) {
    $name = CRM_Core_DAO::escapeString($name);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_value_revenue_sharing_11 SET contribution_chapter_278='$id' WHERE chapter_77='$name'");
  }

  _crs_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function crs_civicrm_uninstall() {

  CRM_Core_DAO::executeQuery("DROP TABLE `contribution_page_revenue_sharing`");

  _crs_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function crs_civicrm_enable() {
  _crs_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function crs_civicrm_disable() {
  _crs_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function crs_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _crs_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function crs_civicrm_managed(&$entities) {
  _crs_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function crs_civicrm_caseTypes(&$caseTypes) {
  _crs_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function crs_civicrm_angularModules(&$angularModules) {
_crs_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function crs_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _crs_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function crs_civicrm_preProcess($formName, &$form) {

}

*/
