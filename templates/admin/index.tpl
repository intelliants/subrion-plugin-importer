<form action="{$smarty.const.IA_SELF}" method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
		
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group js-options">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>
		
			<div class="row">
				<label class="col col-lg-2 control-label" for="input-get-file">{lang key='select_file'}</label>
				<div class="col col-lg-4">
					<select name="get_file" id="input-get-file">
						<option>{lang key='select_file'}</option>
						{foreach $files as $file_name}
							<option value="{$file_name}">{$file_name}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row js-options-csv hide">
				<label class="col col-lg-2 control-label" for="input-delimiter">{lang key='delimiter'}</label>
				<div class="col col-lg-4">
					<input type="text" name="delimiter" value="" id="input-delimiter">
				</div>
			</div>
			<div class="row js-options-csv hide">
				<label class="col col-lg-2 control-label" for="input-as-column">{lang key='use_as_column_name'}</label>
				<div class="col col-lg-4">
					<input type="checkbox" name="as_column" value="" id="input-as-column">
				</div>
			</div>

			<div class="row js-options-xml hide">
				<label class="col col-lg-2 control-label" for="input-entry-tag">{lang key='entry_tag'}</label>
				<div class="col col-lg-4">
					<input type="text" name="entry_tag" value="" id="input-entry-tag">
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label"></label>
				<div class="col col-lg-4">
						<button name="check" class="btn btn-primary js-check"><i class="i-refresh"></i> {lang key="check_file"}</button>
						<span class="help-inline"></span>
					</p>
				</div>
			</div>
			
			<div class="js-options hide">
				<div class="row">
					<label class="col col-lg-2 control-label" for="input-adapter">{lang key='select_adapter'}</label>
					<div class="col col-lg-4">
						<select name="adapter" id="input-adapter">
							<option>{lang key='select'}</option>
							{foreach $adapters as $adapter}
								<option value="{$adapter}">{$adapter}</option>
							{/foreach}
						</select>
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label" for="input-item">{lang key='item'}</label>
					<div class="col col-lg-2">
						<select name="item" id="js-input-item"{if count($items) == 1} disabled="disabled"{/if}>
							<option value="">{lang key='select'}</option>
							{foreach $items as $item}
								<option value="{$item}">{lang key=$item}</option>
							{/foreach}
						</select>
					</div>
					<div class="col col-lg-2">
						<input id="js-input-table" type="text" name="table">
					</div>
					<p><button class="btn btn-primary" id="js-get-fields">{lang key="get_fields"}</button></p>
				</div>
			</div>
		</div>

		<div class="wrap-group js-fields hide">
			<div class="wrap-group-heading">
				<h4>{lang key='fields'}</h4>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='assign_fields'}</label>
				<div class="col col-lg-3">
					<label class="control-label">{lang key='item_fields'}</label>
				</div>
				<div class="col col-lg-3">
					<label class="control-label">{lang key='import_fields'}</label>
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label"></label>
				<div class="col col-lg-3">
					<select name="item" class="js-item-field"></select>
				</div>
				<div class="col col-lg-3">
					<select name="item" class="js-import-field"></select>
				</div>
				<div class="col col-lg-1">
					<button class="btn js-add-row"><i class="i-plus"></i></button>
					<button class="btn js-delete-row"><i class="i-close"></i></button>
				</div>
			</div>
		</div>

		<div class="form-actions inline">
			<button type="submit" id="js-import" name="import" class="btn btn-primary">{lang key='start_import'}</button>
		</div>
		
	</div>
</form>

<div class="modal fade js-process-import">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">{lang key='import_process'}</h4>
			</div>
			<div class="modal-body">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
						<span class="sr-only">0% Complete</span>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="js-stop-import">{lang key='stop'}</button>
				<button type="button" class="btn btn-default" id="js-modal-close">{lang key='close'}</button>
			</div>
		</div>
	</div>
</div>

{ia_add_media files='js:_IA_URL_plugins/importer/js/admin/index'}