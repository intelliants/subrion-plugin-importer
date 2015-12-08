<?php
//##copyright##

class iaAdAgents extends iaImporter
{

	public function before_row_import($row)
	{
		die();
	}

	public function process_username($data)
	{
		if (!$data)
		{
			return false;
		}

		$domain = parse_url($data, PHP_URL_HOST);
		$domain = str_replace(array('.co.uk', '.ltd.uk', 'www.'), '', $domain);

		return $domain;
	}
}