<?php
/**
 * Item Relations
 * @copyright Copyright 2010-2014 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

class FileRelations_AjaxController extends Omeka_Controller_AbstractActionController
{
    public function fileajaxAction()
  	{
  		$title = strtoupper($this->getParam('q'));
  		$db = get_db();
  		$files = $db->query("SELECT record_id, CONCAT(text, ' [', record_id, ']') text FROM `$db->ElementTexts` WHERE element_id = 50 AND record_type = 'File' AND UPPER(text) LIKE '%$title%' ORDER BY text ASC")->fetchAll();
  		$this->_helper->json($files);
  	}
}
