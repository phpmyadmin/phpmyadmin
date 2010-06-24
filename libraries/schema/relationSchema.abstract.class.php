<?php
// Using Abstract Factory Pattern for exporting relational Schema in different Formats !
abstract class exportRelationSchema
{
	private $pageTitle; // Title of the page
	private  $autoLayoutType; // Internal or Foreign Key Relations;
	
	public function setPageTitle($title)
	{
		$this->pageTitle=$title;
	}
	
	public function createPage()
	{
		
	}
	public function createPageHTML()
	{
	?>
	<form method="post" action="pdf_pages.php" name="crpage">
		<fieldset>
		<legend>
		<?php echo __('Create a page') . "\n"; ?>
		</legend>
		<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
		<input type="hidden" name="do" value="createpage" />
		<table>
		<tr>
		<td><label for="id_newpage"><?php echo __('Page name'); ?></label></td>
		<td><input type="text" name="newpage" id="id_newpage" size="20" maxlength="50" /></td>
		</tr>
		<tr>
		<td><?php echo __('Automatic layout based on'); ?></td>
		<td>
			<input type="checkbox" name="auto_layout_internal" id="id_auto_layout_internal" /><label for="id_auto_layout_internal"><?php echo __('Internal relations'); ?></label><br />
		<?php
		if (PMA_StorageEngine::isValid('InnoDB') || PMA_StorageEngine::isValid('PBXT')) {
		?>
			<input type="checkbox" name="auto_layout_foreign" id="id_auto_layout_foreign" /><label for="id_auto_layout_foreign">FOREIGN KEY</label><br />
		<?php
		}
		?>
		</td></tr>
		</table>
		</fieldset>
		<fieldset class="tblFooters">
		<input type="submit" value="<?php echo __('Go'); ?>" />
		</fieldset>
	</form>
<?php
	}
}
?>