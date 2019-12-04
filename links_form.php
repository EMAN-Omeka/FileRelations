<script type="text/javascript" src="<?php echo WEB_ROOT; ?>/plugins/FileRelations/javascripts/ajax.js"></script>
<p>
<?php
$link = '<a href="' . url('item-relations/vocabularies/') . '">'
      . __('Browse Vocabularies') . '</a>';

echo __('Here you can relate this item to another item and delete existing '
     . 'relations. For descriptions of the relations, see the %s page. Invalid '
     . 'item IDs will be ignored.', $link
);
?>
</p>
<table>
    <thead>
    <tr>
        <th><?php echo __('Sujet'); ?></th>
        <th><?php echo __('Relation'); ?></th>
        <th><?php echo __('Objet'); ?></th>      
        <th style="width:20px;"><?php echo 'Sup.' ?></th>
    </tr>
    </thead>
    <tbody>
    <?php 
      $formSelectProperties = get_table_options('ItemRelationsProperty');      
      foreach ($subjectRelations as $subjectRelation): ?>
    <tr>
        <td><?php echo __('This File'); ?></td>
        <td><?php echo $subjectRelation['relation_text']; ?></td>
        <td><a href="<?php echo url('files/show/' . $subjectRelation['object_file_id']); ?>" target="_blank"><?php echo $subjectRelation['object_file_title']; ?></a></td>
        <td><input type="checkbox" name="file_relations_file_relation_delete[]" value="<?php echo $subjectRelation['file_relation_id']; ?>" /></td>
        <tr>        
				<td colspan='4'>
        <?php if ($subjectRelation) { ?>
        						<label>Commenter cette relation <i>(200 caract&egrave;res maximum)</i></label>
                    <input name="file_relations_subject_comment[<?php echo $subjectRelation['file_relation_id']; ?>]"
                    id="file_relations_subject_comment_<?php echo $subjectRelation['file_relation_id']; ?>"
                    size="60" maxlength="200" value="<?php echo $subjectRelation['relation_comment'];  ?>" />
                <?php }
                else {
                     echo $relation['relation_comment'];
                } ?>
        </td>
        </tr>        
    </tr>
    <?php endforeach; ?>
    <?php foreach ($objectRelations as $objectRelation): ?>
    <tr>
        <td><a href="<?php echo url('files/show/' . $objectRelation['subject_file_id']); ?>" target="_blank"><?php echo $objectRelation['subject_file_title']; ?></a></td>
        <td><?php echo $objectRelation['relation_text']; ?></td>
        <td><?php echo __('This File'); ?></td>
        <td><input type="checkbox" name="file_relations_file_relation_delete[]" value="<?php echo $objectRelation['file_relation_id']; ?>" /></td>
    </tr>
    <?php endforeach; ?>
    <tr class="file-relations-entry">
        <td><?php echo __('This File'); ?></td>
        <td>Relation<br /><?php echo get_view()->formSelect('file_relations_property_id[]', null, array('multiple' => false), $formSelectProperties); ?></td>
        <td><div class='ui-widget'><?php echo __('File Title'); ?> 
        <?php echo get_view()->formText('file_relations_file_relation_object_file_id[]', null, array('size' => 8, 'class' => 'fileId', 'style' => 'display:none;')); ?>
        <input type="text" size="12" class="file-relations-autocomplete" />
        </div></td>
        <td><span style="color:#ccc;">n/a</span></td>
    </tr>
    </tbody>
</table>
<button type="button" class="file-relations-add-relation"><?php echo __('Add a Relation'); ?></button>
<input type="hidden" id="phpWebRoot" value="<?php echo WEB_ROOT; ?>">
<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery('.file-relations-add-relation').click(function () {
        var oldRow = jQuery('.file-relations-entry').last();
        var newRow = oldRow.clone();
        oldRow.after(newRow);
        var inputs = newRow.find('input, select');
        inputs.val('');
    });
});
</script>
