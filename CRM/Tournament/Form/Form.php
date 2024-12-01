<?php

/**
 * Form short summary.
 *
 * Form description.
 *
 * @version 1.0
 * @author msteigerwald
 */

use CRM_Tournament_ExtensionUtil as E;
  
class Tournament_Core_Form extends CRM_Core_Form
{
  protected $_values;
  protected $_id;
  protected $_fieldNames;
  protected $_recordName;
  protected $_updateAction;

  public function preProcess()
  {
    parent::preProcess();

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'update');

    if ($this->_action == CRM_Core_Action::ADD) {
      $this->addRecord();
    } else if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->updateRecord();
    }
 
  }

  public function buildQuickForm()
  {
    $this->applyFilter('__ALL__', 'trim');
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
  }

  public function postProcess()
  {
    foreach ($this->_fieldNames as $fieldName) {
      $this->_updateAction->addValue($fieldName, $this->_values[$fieldName]);
    }

    $this->_updateAction->execute();

    $session = CRM_Core_Session::singleton();
    $session->setStatus($this->_recordName, "$this->_name Saved", 'success');

    $this->updateTitle();
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database
   */
  public function setDefaultValues()
  {
    $defaults = $this->_values;
    return $defaults;
  }  

  /**
   * Default form context used as part of addField()
   */
  public function getDefaultContext(): string
  {
    return 'create';
  }

  public function getRenderableElementNames()
  {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  protected function updateRecord()
  {
    $getAction = $this->getGetSingleRecordAction();

    foreach ($this->_fieldNames as &$fieldName) {
      $getAction = $getAction->addSelect($fieldName);
    }

    $result = $getAction->execute();

    // Check if record was found
    if (!$result) {
      CRM_Core_Error::statusBounce(ts("Could not find $this->_name with id = $this->_id"));
    }

    $this->_values = $result[0];

    if (empty($this->_values['id'])) {
      CRM_Core_Error::statusBounce(ts("Could not find $this->_name with id = $this->_id"));
    }

    if (!CRM_Core_Permission::check('edit ' . $this->getDefaultEntity() . 's', $this->_id)) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }

    $this->updateTitle();
  }

  protected function updateTitle()
  {
    $this->setTitle(ts('Edit %1', [1 => $this->_recordName]));
  }

  protected function addRecord()
  {
    // check for add contacts permissions
    if (!CRM_Core_Permission::check('add ' . $this->getDefaultEntity() . 's')) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }

    $this->setTitle(ts('New %1', [1 => $this->_name]));

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/dashboard', 'reset=1'));
  }
}