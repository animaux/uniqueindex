<?php
	
	class Extension_UniqueIndex extends Extension {
		
		/**
		* Extension meta data
		*/
		public function about() {
			return array(
				'name'			=> 'Unique Index',
				'version'		=> '1.0',
				'release-date'	=> '2011-12-20',
				'author'		=> array(
					'name'			=> 'Guillem Lorman'
				),
				'description' => 'Define a unique index for the section and check if for it before save data.'
			);
		}
		
		public static function isUnique($field_ids, $field_names, $field_types, $entry_id) {
			$field_ids = explode(',', $field_ids);
			$field_names = explode(',', $field_names);
			$field_types = explode(',', $field_types);

			$post = General::getPostData();
			$exist_count = 0;

			$idx = 0;
			foreach ($field_ids as $field_id) {
				
				// Select names of the field_id
				$field_name = $field_names[$idx];
				
				$post_value = $post['fields'][$field_name];
				
				// Select entry_id for the field_ids with post values.
				$field_type = $field_types[$idx];
				
				$value_col = "value='".$post_value."'";
				if (is_array($post_value)) 				
					$value_col = "value IN ('".implode(',',$post_value)."')";
				
				switch($field_type) {
					case 'selectbox_link':
						$value_col = 'relation_id='.$post_value;
						break;
				}
				
				$entries = Symphony::Database()->fetch(
					sprintf("SELECT entry_id FROM `tbl_entries_data_%d` WHERE ".$value_col." AND entry_id != %d", $field_id, $entry_id)
				);

				if (count($entries) > 0)
					$exist_count++;
				$idx ++;
			}
			
			// if number of existing values are = to the unique filds id is another exact value exist in the section
			return $exist_count != count($field_ids); 
		}
		
		public function install(){
			return $this->_Parent->Database->query(
				"CREATE TABLE `tbl_fields_uniqueindex` (
				 `id` int(11) unsigned NOT NULL auto_increment,
				 `field_id` int(11) unsigned NOT NULL,
				 `unique_field_ids` varchar(50),
				 `unique_field_names` varchar(50),
				 `unique_field_labels` varchar(50),
				 `unique_field_types` varchar(50),
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				) ENGINE=MyISAM;"
			);
		}

		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_uniqueindex`");
		}


	}
	