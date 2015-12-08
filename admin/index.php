<?php
//##copyright##

$iaImporter = $iaCore->factoryPlugin('importer', iaCore::ADMIN);

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$output = array();

	$file['path'] = IA_UPLOADS . 'importer/';

	$allowed_ext = array(
		'text/csv' => 'csv',
		'text/xml' => 'xml'
	);

	$methods = array();
	$adapter_file = $_POST['adapter'] ? $_POST['adapter'] : $_GET['adapter'];


	if ($adapter_file)
	{
		if ($adapter = $iaImporter->loadAdapter($adapter_file))
		{
			$methods = get_class_methods($adapter);
		}
	}

	switch ($_GET['action'])
	{
		case 'start_import_process':
			if (in_array('start_import_process', $methods))
			{
				$adapter->start_import_process();
			}
			break;

		case 'finish_import_process':
			if (in_array('finish_import_process', $methods))
			{
				$adapter->finish_import_process();
			}
			break;

		case 'check_file':
			$file['ext'] = substr(strrchr($_GET['file'], "."), 1);

			if ($file['ext'] == 'csv')
			{
				$path = $file['path'] . $_GET['file'];
				$fopen = fopen($path, "r");
				while(!feof($fopen))
				{
					$line = fgets($fopen);
					$lineCount++;
				}
				fclose($fopen);
				$output['total'] = $lineCount;

				$data = $iaImporter->readFile($path, 0, 1024);
				$fields = str_getcsv($data['rows'][0], $_GET['delimiter']);
				if (!$_GET['as_column'])
				{
					foreach ($fields as $key => $value)
					{
						$fields[$key] = 'column_' . $key;
					}
				}
				$output['fields'] = $fields;
			}

			break;

		case 'change_item':

			$iaItem = $iaCore->factory('item');
			$items = $iaItem->getItems();
			if (in_array($_GET['item'], $items))
			{
				$output['table'] = $iaItem->getItemTable($_GET['item']);
			}
			break;

		case 'get_fields':

			$iaDbControl = $iaCore->factory('dbcontrol', iaCore::ADMIN);
			$tables = $iaDbControl->getTables();
			$table = $iaDb->prefix . $_GET['table'];
			if (in_array($table, $tables))
			{
				$item_fields = $iaDb->describe($table, false);
				if ($item_fields)
				{
					foreach ($item_fields as $field)
					{
						$output['item_fields'][] = $field['Field'];
					}
				}
			}
			break;
	}

	if ($_POST['get_file'])
	{
		$file['ext'] = substr(strrchr($_POST['get_file'], '.'), 1);
		$fields = array_combine($_POST['item_fields'], $_POST['import_fields']);

		$start = $_POST['start'] ? $_POST['start'] : 0;
		$path = $file['path'] . $_POST['get_file'];

		if ($file['ext'] == 'csv')
		{
			$data = $iaImporter->readFile($path, $start);

			if ($data['rows'] && $fields)
			{
				if (0 == $_POST['start'] && $_POST['as_column'])
				{
					unset($data['rows'][0]);
				}
				
				if (in_array('after_get_rows', $methods))
				{
					$adapter->after_get_rows[$data['rows']];
				}

				$iaDb->setTable($_POST['table']);
				foreach ($data['rows'] as $i => $row)
				{
					$parsed = str_getcsv($row, $_POST['delimiter']);

					if (isset($parsed[0]) && !empty($parsed[0]))
					{
						if (in_array('before_row_import', $methods))
						{
							$adapter->before_row_import($parsed);
						}

						foreach ($fields as $field => $key)
						{
							$method = 'process_' . $field;
							$import[$field] = in_array($method, $methods) ? $adapter->$method($parsed[$key]) : $parsed[$key];
						}
						
						$ids[$i] = $id = $iaDb->insert($import);

						if (in_array('after_row_import', $methods))
						{
							$adapter->after_row_import($id);
						}
					}
				}
				$iaDb->resetTable();

				if (in_array('after_all_rows_import', $methods))
				{
					$adapter->after_all_rows_import($ids);
				}

				$output['imported'] = count($ids);
				$output['start'] = $data['start'];
				$output['done'] = $data['end'];
			}
			else
			{
				$output['error'] = true;
			}
			
		}

	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaCore::ACTION_ADD == $pageAction)
	{
		$error = false;

		$file['path'] = IA_UPLOADS . 'importer/';

		$allowed_ext = array(
			'text/csv' => 'csv',
			'text/xml' => 'xml'
		);

		if (isset($_POST['param']['file']) && !empty($_FILES['file']))
		{
			$file['name'] = $_FILES['file']['name'];
			$file['ext'] = substr(strrchr($file['name'], "."), 1);

			if (!$file['name'] || !in_array($file['ext'], $allowed_ext, true))
			{
				$error = true;
				$messages[] = iaLanguage::get('incorrect_file');
			}

			if (file_exists($file['path'] . $file['name']))
			{
				$error = true;
				$messages[] = iaLanguage::get('file_already_exists');
			}

			if (!$error)
			{
				@move_uploaded_file($_FILES['file']['tmp_name'], $file['path'] . $file['name']);

				$messages[] = iaLanguage::get('file_uploaded');
				$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));

				iaUtil::go_to(IA_ADMIN_URL . 'importer/');
			}
			else
			{
				$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
			}

		}
		elseif (isset($_POST['file_url']) && !empty($_POST['file_url']))
		{
			$file['url'] = $_POST['file_url'];
			$file['name'] = substr(strrchr($file['url'], "/"), 1);
			$file['ext'] = substr(strrchr($file['name'], "."), 1);

			if (!$file['name'] || !in_array($file['ext'], $allowed_ext))
			{
				$error = true;
				$messages[] = iaLanguage::get('incorrect_file');
			}

			if (file_exists($file['path'] . $file['name']))
			{
				$error = true;
				$messages[] = iaLanguage::get('file_already_exists');
			}

			if (!fopen($file['url'], 'r'))
			{
				$error = true;
				$messages[] = iaLanguage::get('cant_get_file');
			}

			if (!$error)
			{
				$return = file_put_contents($file['path'] . $file['name'], fopen($file['url'], 'r'));

				if ($return)
				{
					$messages[] = iaLanguage::get('file_uploaded');
					$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));

					iaUtil::go_to(IA_ADMIN_URL . 'importer/');
				}
				else
				{
					$error = true;
					$messages[] = iaLanguage::get('upload_failed');
					$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
				}
			}
			else
			{
				$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
			}
		}

		if (!file_exists($file['path']))
		{
			mkdir($file['path'], 0755);
		}

		$permissions = is_writable($file['path']);
		if (!$permissions)
		{
			$permissions = chmod($file['path'], 755);
		}

		$allowed_size = ini_get('upload_max_filesize');
		$iaView->assign('allowed_size', $allowed_size);
		$iaView->assign('permissions', $permissions);

		$iaView->display('upload');
	}
	else
	{
		$files = $iaImporter->listFiles(IA_UPLOADS . 'importer/');
		$adapters = $iaImporter->listFiles(IA_PLUGINS . 'importer/includes/adapters/');

		$iaItem = $iaCore->factory('item');
		$items = $iaItem->getItems();

		$iaView->assign('items', $items);
		$iaView->assign('files', $files);
		$iaView->assign('adapters', $adapters);
		$iaView->display('index');
	}
}