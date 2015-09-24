<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Crs_Form_RevenueSharing extends CRM_Contribute_Form_ContributionPage {


  function buildQuickForm() {
    $values = array(
      CRS_REGION_NONE => 'Leave blank / NULL',
      CRS_REGION_USER => 'Let the contributor select',
      CRS_REGION_CHAPTER => 'Use the region of the selected chapter',
      CRS_REGION_POSTAL => 'Calculate from contributors\' postal code',
      CRS_REGION_SELECTED => '',
    );
    $this->addRadio('region_mode', ts('Region'), $values, array(), '<br />', true);

    $values = array(
      CRS_CHAPTER_NONE => 'Leave blank / NULL',
      CRS_CHAPTER_SELECTED => '',
    );
    $this->addRadio('chapter_mode', ts('Chapter'), $values, array(), '<br />', true);

    // create form elements for region_76 and chapter_77
    $detail = CRM_Core_BAO_CustomGroup::getGroupDetail(CRS_GROUP_ID);
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($detail, TRUE, $this);

    foreach ($groupTree[CRS_GROUP_ID]['fields'] as &$field) {
      CRM_Core_BAO_CustomField::addQuickFormElement($this, $field['column_name'], $field['id'], FALSE, CRM_Utils_Array::value('is_required', $field));
    }

    parent::buildQuickForm();
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['region_mode'] = CRS_REGION_NONE;
    $defaults['chapter_mode'] = CRS_CHAPTER_NONE;
    $defaults['region_contact_id'] = null;
    $defaults['chapter_contact_id'] = null;
    $defaults['region_76'] = 'none';
    $defaults['chapter_77'] = 'Unassigned';

    if (isset($this->_id)) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
      CRM_Utils_System::setTitle(ts('Revenue Sharing') . " ($title)");
    
      $dao = new CRM_Crs_DAO_RevenueSharing();
      $dao->contribution_page_id = $this->_id;
      $dao->find(TRUE);
      CRM_Core_DAO::storeValues($dao, $defaults);

      // BEGIN REMOVE LATER
      $defaults['region_76'] = crs_contact_to_name($defaults['region_contact_id'], 'none');
      $defaults['chapter_77'] = crs_contact_to_name($defaults['chapter_contact_id'], 'Unassigned');
      // END REMOVE LATER
    }

    return $defaults;
  }

  function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // make sure submitted region and chapter are contact references, or null if not applicable
    if ($params['region_mode'] != CRS_REGION_SELECTED)
      $params['region_contact_id'] = null;
    else  // REMOVE LATER
      $params['region_contact_id'] = crs_region_name_to_contact($params['region_76']);
    
    if ($params['chapter_mode'] != CRS_CHAPTER_SELECTED)
      $params['chapter_contact_id'] = null;
    else  // REMOVE LATER
      $params['chapter_contact_id'] = crs_chapter_name_to_contact($params['chapter_77']);

    // create/update the revenue sharing settings for the contribution page
    $dao = new CRM_Crs_DAO_RevenueSharing();
    $dao->contribution_page_id = $this->_id;
    $dao->find(TRUE);
    $dao->copyValues($params);
    $dao->save();
    
    // nulls not saved to db by $dao, have to handle them separately
    $nulls = array();
    if (is_null($params['region_contact_id']))
      $nulls[] = 'region_contact_id=NULL';
    if (is_null($params['chapter_contact_id']))
      $nulls[] = 'chapter_contact_id=NULL';

    if (!empty($nulls))
      CRM_Core_DAO::executeQuery('UPDATE civicrm_contribution_page_revenue_sharing SET ' .
                                  implode(',', $nulls) . ' WHERE contribution_page_id=' . $this->_id);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Revenue Sharing');
  }

}