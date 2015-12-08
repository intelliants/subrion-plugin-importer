<?php
//##copyright##

class iaAdYellowpages extends iaImporter
{
	const ALIAS_SUFFIX = '.html';


	public function start_import_process()
	{
		$sql = "TRUNCATE TABLE `{$this->iaDb->prefix}blog_entries`";
		$this->iaDb->query($sql);
	}

	public function process_alias($data)
	{
		$result = iaSanitize::tags($data);

		iaUtil::loadUTF8Functions('ascii', 'utf8_to_ascii');
		utf8_is_ascii($result) || $result = utf8_to_ascii($result);

		$result = rtrim($result, self::ALIAS_SUFFIX);
		$result = iaSanitize::alias($result);
		$result = substr($result, 0, 150); // the DB scheme applies this limitation
		$result.= self::ALIAS_SUFFIX;

		return $result;
	}
}