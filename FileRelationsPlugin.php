<?php
  
  class FileRelationsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_filters = array(
        'admin_files_form_tabs',
      );
    protected $_hooks = array(
        'define_routes',     
        'after_save_file',
        'install',
        'uninstall',   
    );    
    
    public function filterAdminFilesFormTabs($tabs, $args) {
        $file = $args['file'];
        $formSelectProperties = get_table_options('ItemRelationsProperty');
        $subjectRelations = self::prepareSubjectRelations($file);
        $objectRelations = self::prepareObjectRelations($file);        
        ob_start();
        include 'links_form.php';
        $content = ob_get_contents();
        ob_end_clean();
        $tabs['File Relations'] = $content;
        return $tabs;      
    }
 
     /**
     * Install the plugin.
     */
    public function hookInstall()
    {
      $db = get_db();
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->FileRelationsRelation` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `subject_file_id` int(10) unsigned NOT NULL,
            `property_id` int(10) unsigned NOT NULL,
            `object_file_id` int(10) unsigned NOT NULL,
        		`relation_comment` varchar(200) NOT NULL DEFAULT '',             
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);
    }


    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $db = $this->_db;

        // Drop the relations table.
        $sql = "DROP TABLE IF EXISTS `$db->FileRelationsRelation`";
        $db->query($sql);

    }

    /**
     * Save the file relations after saving an file add/edit form.
     *
     * @param array $args
     */
    public function hookAfterSaveFile($args)
    {    	    	    	    	
        if (!$args['post']) {
            return;
        }

        $record = $args['record'];
        $post = $args['post'];

        $db = $this->_db;
        					
        // Save item relations.
        foreach ($post['file_relations_property_id'] as $key => $propertyId) {
            self::insertFileRelation(
                $record,
                $propertyId,
                $post['file_relations_file_relation_object_file_id'][$key]
            );
        }
       
        // Delete item relations.
        if (isset($post['file_relations_file_relation_delete'])) {
            foreach ($post['file_relations_file_relation_delete'] as $itemRelationId) {
                $fileRelation = $db->getTable('FileRelationsRelation')->find($itemRelationId);
                // When an item is related to itself, deleting both relations
                // simultaneously will result in an error. Prevent this by
                // checking if the item relation exists prior to deletion.
                if ($fileRelation) {
                    $fileRelation->delete();
                }
            }
        }
            // update the comment when the comment is edited in subject
        if (isset($post['file_relations_subject_comment'])) {
        	if (isset($post['file_relations_subject_comment'])) {
        		$comments = array();
        		foreach($post['file_relations_subject_comment'] as $key => $value) {
//         			if ($value) {
        				$comments[$key] = $value;
//         			}
        		}
        		if ($comments) {
	        		$commentIds = implode(',', array_keys($comments));
	        		// Optimized the update query to avoid multiple execution.
	        		$sql = "UPDATE `$db->FileRelationsRelation` SET relation_comment = CASE id ";
	        		foreach ($comments as $commentId => $comment) {
	        			$sql .= sprintf(' WHEN %d THEN %s', $commentId, $db->quote($comment));
	        		}
	        		$sql .= " END WHERE id IN ($commentIds)";
	        		$db->query($sql);
        		}
        	}
        	else {
        		$this->_helper->flashMessenger(__('There was an error in the file relation comments.'), 'error');
        	}
        }
    }    
    
    public function hookDefineRoutes($args) {
  		$router = $args['router'];
   		$router->addRoute(
  				'file_relations_ajax_files_autocomplete',
  				new Zend_Controller_Router_Route(
  						'filerelationsajax/:title', 
  						array(
  								'module' => 'file-relations',
  								'controller'   => 'ajax',
  								'action'       => 'fileajax',
  								'title'					=> ''
  						)
  				)
  		);
    }
    
    /**
     * Prepare subject item relations for display.
     *
     * @param Item $item
     * @return array
     */
    public static function prepareSubjectRelations(File $file)
    {
        $subjects = get_db()->getTable('FileRelationsRelation')->findBySubjectFileId($file->id);
        $subjectRelations = array();

        foreach ($subjects as $subject) {
            if (!($file = get_record_by_id('file', $subject->object_file_id))) {
                continue;
            }           
            $subjectRelations[] = array(
                'file_relation_id' => $subject->id,
                'object_file_id' => $subject->object_file_id,
                'object_file_title' => self::getFileTitle($file),
            		'relation_comment' => $subject->relation_comment,            		
                'relation_text' => $subject->getPropertyText(),
                'relation_description' => $subject->property_description,
/*
            		'item_thumbnail' => self::getItemThumbnail($subject->object_item_id),            		
            		'item_collection' => $collectionTitle
*/
            );
        }
        if ($subjectRelations) {
					$subjectRelations = self::sortByRelationTitle($subjectRelations, 'relation_text', 'object_file_title', 'relation_description');
        }       
        return $subjectRelations;
    }

    /**
     * Prepare object item relations for display.
     *
     * @param Item $item
     * @return array
     */
    public static function prepareObjectRelations(File $file)
    {
        $objects = get_db()->getTable('FileRelationsRelation')->findByObjectfileId($file->id);
        $objectRelations = array();
        foreach ($objects as $object) {
            if (!($file = get_record_by_id('file', $object->subject_file_id))) {
                continue;
            }
            $objectRelations[] = array(
                'file_relation_id' => $object->id,
                'subject_file_id' => $object->subject_file_id,
                'subject_file_title' => self::getFileTitle($file),
            		'relation_comment' => $object->relation_comment,            		
                'relation_text' => $object->getPropertyText(),
                'relation_description' => $object->property_description,
/*
            		'item_thumbnail' => self::getItemThumbnail($object->subject_item_id),
            		'subject_collection' => $collectionTitle
*/
            );
        }       
        if ($objectRelations) {
        	$objectRelations = self::sortByRelationTitle($objectRelations, 'relation_text', 'subject_file_title', 'relation_description');
        }
        return $objectRelations;
    }  
    
    /**
     * Return a item's title.
     *
     * @param Item $item The item.
     * @return string
     */
    public static function getFileTitle($file)
    {
        $title = metadata($file, array('Dublin Core', 'Title'), array('no_filter' => true));
        if (!trim($title)) {
            $title = '#' . $file->id;
        }
        return $title;
    }  
    /**
     * Return an associative array sorted by 1 column then another. 
     *
     * @param associative array to sort  
     * @param first column's name 
     * @param second column's name
     * @return array
     */
    public static function sortByRelationTitle($array, $firstSort, $secondSort, $thirdSort) {
    	foreach ($array as $key => $row) {
    		$sort1[$key]  = $row[$firstSort];
    		$sort2[$key] = $row[$secondSort];
    		$sort3[$key] = $row[$thirdSort];
    	}
    	array_multisort($sort1, SORT_STRING, $sort2, SORT_STRING, $sort3, SORT_STRING, $array);
    	return $array;
    }
    /**
     * Insert an item relation.
     *
     * @param Item|int $subjectItem
     * @param int $propertyId
     * @param Item|int $objectItem
     * @return bool True: success; false: unsuccessful
     */
    public static function insertFileRelation($subjectFile, $propertyId, $objectFile)
    {
        // Only numeric property IDs are valid.
        if (!is_numeric($propertyId)) {
            return false;
        }

        // Set the subject item.
        if (!($subjectFile instanceOf File)) {
            $subjectFile = get_db()->getTable('File')->find($subjectFile);
        }

        // Set the object item.
        if (!($objectFile instanceOf File)) {
            $objectFile = get_db()->getTable('File')->find($objectFile);
        }

        // Don't save the relation if the subject or object items don't exist.
        if (!$subjectFile || !$objectFile) {
            return false;
        }

        $file = new FileRelationsRelation;
        $file->subject_file_id = $subjectFile->id;
        $file->property_id = $propertyId;
        $file->object_file_id = $objectFile->id;
        $file->relation_comment = strlen($relationComment) ? $relationComment : '';        
        $file->save();

        return true;
    }            
}