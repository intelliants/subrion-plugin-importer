<?php
//##copyright##

class iaImporter extends abstractPlugin
{
	protected $_adapterInstances = array();


	public function loadAdapter($filename)
	{
		$classFile = IA_PLUGINS . 'importer' . IA_DS . 'includes' . IA_DS . 'adapters' . IA_DS . $filename;
		$class = explode('.', $filename);
		$className = 'iaAd' . ucfirst($class[0]);

		if (file_exists($classFile))
		{
			include_once $classFile;

			$this->_adapterInstances[$className] = new $className();
			$this->_adapterInstances[$className]->init();

			return $this->_adapterInstances[$className];
		}
	}

	public function readFile($path, $start = 0, $size = 40960)
	{
		$fopen = fopen($path, "r");
		fseek($fopen, $start, SEEK_SET);
		$content = fread($fopen, $size);
		$last_pos = ftell($fopen);
		fclose($fopen);

		if ($last_pos != filesize($path))
		{
			$pos = strrpos($content, PHP_EOL);
			$content = substr($content, 0, $pos);
			$data['end'] = false;
		}
		else
		{
			$data['end'] = true;
		}

		$data['start'] = $start + strlen($content);
		$data['rows'] = explode(PHP_EOL, $content);

		return $data;
	}

	public function listFiles($path)
	{
		$all_files = scandir($path);
		foreach ($all_files as $file)
		{
			if ($file != "." && $file != "..")
			{
				$files[] = $file;
			}
		}

		return $files;
	}
}