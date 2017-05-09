<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/fields/field.input.php');

	class FieldUniqueIndex extends Field {
		public function __construct(){
			parent::__construct();
			$this->_name = 'Unique Index';
		}

		public function commit() {

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;

			$field_ids = $this->get('unique_field_ids');
			$fields['unique_field_ids'] = implode(',',$field_ids);

			$idx = 0;
			foreach ($field_ids as $field_id) {
				if ($idx > 0) {
					$fields['unique_field_names'] .= ',';
					$fields['unique_field_labels'] .= ',';
					$fields['unique_field_types'] .= ',';
				}
				// Select names, labels of the field_id
				$entry = Symphony::Database()->fetchRow(0,
					sprintf("SELECT element_name, label, type FROM `tbl_fields` WHERE id=%d", $field_id)
				);
				
				$fields['unique_field_names'] .= $entry['element_name'];
				$fields['unique_field_labels'] .= $entry['label'];
				$fields['unique_field_types'] .= $entry['type'];
				
				$idx ++;
			}
							
			
			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		function displaySettingsPanel(XMLElement &$wrapper, $errors=NULL) {
			parent::displaySettingsPanel($wrapper, $errors);

			// get current section id
			$section_id = Administration::instance()->Page->_context[1];

			// related field
			$label = Widget::Label(__('Unique index fields'), NULL);
			$fieldManager = new FieldManager($this->_engine);
			$fields = $fieldManager->fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, '');
			$options = array();
			$attributes = array(
				array()
			);
			$field_ids = explode(',',$this->get('unique_field_ids'));
			if(is_array($fields) && !empty($fields)) {
				foreach($fields as $field) {
					$options[] = array($field->get('id'), in_array($field->get('id'), $field_ids), $field->get('label'));
				}
			};
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][unique_field_ids][]', $options, array('multiple' => 'multiple')));
			if(isset($errors['unique_field_ids'])) {
				$wrapper->appendChild(Widget::Error($label, $errors['unique_field_ids']));
			} else {
				$wrapper->appendChild($label);
			};
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		function displayPublishPanel(XMLElement &$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL, $entry_id = NULL){
			Extension_UniqueIndex::appendAssets();

			$value = $data['value'];
								
			$label = Widget::Label($this->get('label'));
			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			
		}
	
		public function checkPostFieldData($data, &$message, $entry_id = NULL) {
			$field_ids = $this->get('unique_field_ids');
			$field_names = $this->get('unique_field_names');
			$field_types = $this->get('unique_field_types');
			
			$message = NULL;

			$driver = Symphony::ExtensionManager()->create('uniqueindex');
			if (!$driver->isUnique($field_ids, $field_names, $field_types, $entry_id)) {
				$message = __("'%s' contains data which is already used", array(str_replace(',', ', ', $this->get('unique_field_labels'))));
				return self::__INVALID_FIELDS__;
			}
			
			return self::__OK__;
		}
	}

	
