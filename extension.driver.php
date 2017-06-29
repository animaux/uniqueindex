<?php
	
	class Extension_UniqueIndex extends Extension {
		
		protected static $assets_loaded = false;
		
		public static function isUnique($field_ids, $field_names, $field_types, $entry_id) {
			$field_ids = explode(',', $field_ids);
			$field_names = explode(',', $field_names);
			$field_types = explode(',', $field_types);

			$post = General::getPostData();
			$exist_count = 0;

			$idx = 0;
			$values_col = array();
			$field_tables = array();
			
			foreach ($field_ids as $field_id) {
				
				// Select names of the field_id
				$field_name = $field_names[$idx];
				$field_table = 'tbl_entries_data_'.$field_id;
				$field_tables[$idx] = $field_table;
				
				$post_value = $post['fields'][$field_name];
				
				// Select entry_id for the field_ids with post values.
				$field_type = $field_types[$idx];
				
				$value_field = 'value';				
				switch($field_type) {
					case 'pages':
						$value_field = 'page_id';
						break;
					case 'selectbox_link':
						$value_field = 'relation_id';
						break;
          case 'association':
						$value_field = 'relation_id';
						break;
					default:
						;
				}

				if (!empty($post_value)) {
					$values_col[$idx] = $field_table.'.'.$value_field."='".addslashes($post_value)."'";
					if (is_array($post_value)) 				
						$values_col[$idx] = $field_table.'.'.$value_field." IN ('".implode("', '", array_map('mysql_escape_string', $post_value))."')";
				}
				$idx ++;
			}

			// SELECT
			if ($idx > 0) {
				$from_tables = implode(', ', $field_tables);
				$where = implode(' AND ', $values_col);
				
				if (!empty($entry_id))
					$where .=  " AND ".$field_tables[0].".entry_id != ".$entry_id;
				
				$join = '';
				foreach ($field_tables as $table) {
					$join .= $field_tables[0].'.entry_id = '.$table.'.entry_id AND ';
				}
					
				$entries = Symphony::Database()->fetch(
					sprintf("SELECT %s.entry_id FROM %s WHERE %s %s", $field_tables[0], $from_tables, $join, $where)
				);

				return count($entries) === 0;
			}
			
			return true; 
		}
		
		public function install(){
			return Symphony::Database()->query(
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
			Symphony::Database()->query("DROP TABLE `tbl_fields_uniqueindex`");
		}

		public function appendAssets() {
			if (self::$assets_loaded === false) {
				$page = Administration::instance()->Page;
							
				$page->addStylesheetToHead(URL . '/extensions/uniqueindex/assets/uniqueindex.publish.css', 'screen');
				
				self::$assets_loaded = true;
			}
		}

	}
	