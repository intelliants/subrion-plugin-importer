{if $permissions}
<form action="{$smarty.const.IA_SELF}" method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
		
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='upload_file'}</h4>
			</div>
			<div class="row">
				<div class="col"><p class="alert alert-info">{lang key='allowed_extensions'}</p></div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label" for="input-file">{lang key='upload_file'}</label>
				<div class="col col-lg-4">
					{ia_html_file name='file' id='input-file'}
					<span class="help-block">{lang key="max_allowed_size_for_upload"}: {$allowed_size}</span>
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label" for="input-file">{lang key='url_to_file'}</label>
				<div class="col col-lg-4">
					<input type="text" name="file_url" placeholder="http://mysite.com/links.csv" value="" id="input-file_url">
				</div>
			</div>
		</div>
		<div class="form-actions inline">
			<button name="upload" class="btn btn-primary"><i class="i-upload"></i> {lang key="upload_file"}</button>
		</div>
	</div>
</form>
{else}
<div class="wrap-list">
	<div class="wrap-group">
		<div class="wrap-group-heading">
			<h4>{lang key='upload_file'}</h4>
		</div>
		<div class="row">
			<div class="col"><p class="alert alert-error">{lang key='check_permission'}</p></div>
		</div>
	</div>
</div>
{/if}