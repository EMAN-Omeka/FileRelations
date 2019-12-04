<?php
/**
 * Item Relations
 * @copyright Copyright 2010-2014 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Item Relations Relation table.
 */
class Table_FileRelationsRelation extends Omeka_Db_Table
{
    /**
     * Get the default select object.
     *
     * Automatically join with both Property and Vocabulary to get all the
     * data necessary to describe a whole relation.
     *
     * @return Omeka_Db_Select
     */
    public function getSelect()
    {
        $db = $this->getDb();
        return parent::getSelect()
            ->join(
                array('item_relations_properties' => $db->ItemRelationsProperty),
                'file_relations_relations.property_id = item_relations_properties.id',
                array(
                    'property_vocabulary_id' => 'vocabulary_id',
                    'property_local_part' => 'local_part',
                    'property_label' => 'label',
                    'property_description' => 'description'
                )
            )
            ->join(
                array('item_relations_vocabularies' => $db->ItemRelationsVocabulary),
                'item_relations_properties.vocabulary_id = item_relations_vocabularies.id',
                array('vocabulary_namespace_prefix' => 'namespace_prefix')
            );
    }

    /**
     * Find item relations by subject item ID.
     * 
     * @return array
     */
    public function findBySubjectFileId($subjectFileId)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
            ->where('file_relations_relations.subject_file_id = ?', (int) $subjectFileId);
        return $this->fetchObjects($select);
    }
    
    /**
     * Find item relations by object file ID.
     * 
     * @return array
     */
    public function findByObjectFileId($objectFileId)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
            ->where('file_relations_relations.object_file_id = ?', (int) $objectFileId);
        return $this->fetchObjects($select);
    }
    
        /**
     * Find item relations by object file ID.
     * 
     * @return array
     */
    public function translate($label, $vocabularyNamespacePrefix, $mode = 'label')
    {
    	if ($vocabularyNamespacePrefix != '') {   
	    	global $trads;
	    	$vocabularyNamespacePrefix = strtoupper($vocabularyNamespacePrefix);
	    	if ($vocabularyNamespacePrefix == 'DCTERMS') {$vocabularyNamespacePrefix = 'DUBLIN CORE';}
	    	$label = strtolower($label);
	    	include_once(PLUGIN_DIR . '/ItemRelations/translations.php');
	    	if (isset($trads[$vocabularyNamespacePrefix][$label])) {
	    		if ($mode == 'description') {
	    			$label = $trads[$vocabularyNamespacePrefix][$label][1];
	    		} else {
	    			$label = $trads[$vocabularyNamespacePrefix][$label][0];	    			
	    		}
	    	}
	    }
	    return $label;
    }
}
