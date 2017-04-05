<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

$iaImporter = $iaCore->factoryPlugin('importer', iaCore::ADMIN);

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    $output = array();

    $file['path'] = IA_UPLOADS . 'importer/';

    $allowed_ext = array(
        'text/csv' => 'csv',
        'text/xml' => 'xml'
    );

    $methods = array();
    $adapter_file = $_POST['adapter'] ? $_POST['adapter'] : $_GET['adapter'];


    if ($adapter_file) {
        if ($adapter = $iaImporter->loadAdapter($adapter_file)) {
            $methods = get_class_methods($adapter);
        }
    }

    switch ($_GET['action']) {
        case 'start_import_process':
            if (in_array('start_import_process', $methods)) {
                $adapter->start_import_process();
            }
            break;

        case 'finish_import_process':
            if (in_array('finish_import_process', $methods)) {
                $adapter->finish_import_process();
            }
            break;

        case 'check_file':
            $file['ext'] = substr(strrchr($_GET['file'], "."), 1);

            if ('csv' == $file['ext']) {
                $path = $file['path'] . $_GET['file'];
                $fopen = fopen($path, "r");
                while (!feof($fopen)) {
                    $line = fgets($fopen);
                    if (!isset($lineCount)) {
                        $firstLine = $line;
                    }
                    $lineCount++;
                }
                fclose($fopen);
                $output['total'] = $lineCount;

                $data = $iaImporter->readFile($path, 0, 1024);
                $fields = str_getcsv($firstLine, $_GET['delimiter']);
                if (!$_GET['as_column']) {
                    foreach ($fields as $key => $value) {
                        $fields[$key] = 'column_' . $key;
                    }
                }
                $output['fields'] = $fields;
            }

            break;

        case 'change_item':

            $iaItem = $iaCore->factory('item');
            $items = $iaItem->getItems();
            if (in_array($_GET['item'], $items)) {
                $output['table'] = $iaItem->getItemTable($_GET['item']);
            }
            break;

        case 'get_fields':

            $iaDbControl = $iaCore->factory('dbcontrol', iaCore::ADMIN);
            $tables = $iaDbControl->getTables();
            $table = $iaDb->prefix . $_GET['table'];
            if (in_array($table, $tables)) {
                $item_fields = $iaDb->describe($table, false);
                if ($item_fields) {
                    foreach ($item_fields as $field) {
                        $output['item_fields'][] = $field['Field'];
                    }
                }
            }
            break;
    }

    if ($_POST['get_file']) {
        $file['ext'] = substr(strrchr($_POST['get_file'], '.'), 1);
        $fields = array_combine($_POST['item_fields'], $_POST['import_fields']);

        $start = $_POST['start'] ? $_POST['start'] : 0;
        $path = $file['path'] . $_POST['get_file'];

        if ('csv' == $file['ext']) {
            $data = $iaImporter->readFile($path, $start);

            if ($data['rows'] && $fields) {
                if (0 == $_POST['start'] && $_POST['as_column']) {
                    unset($data['rows'][0]);
                }

                if (in_array('after_get_rows', $methods)) {
                    $adapter->after_get_rows[$data['rows']];
                }

                $iaDb->setTable($_POST['table']);
                foreach ($data['rows'] as $i => $row) {
                    $parsed = str_getcsv($row, $_POST['delimiter']);

                    if (isset($parsed[0]) && !empty($parsed[0])) {
                        if (in_array('before_row_import', $methods)) {
                            $adapter->before_row_import($parsed);
                        }

                        foreach ($fields as $field => $key) {
                            $method = 'process_' . $field;
                            $import[$field] = in_array($method,
                                $methods) ? $adapter->$method($parsed[$key]) : $parsed[$key];
                        }

                        $ids[$i] = $id = $iaDb->insert($import);

                        if (in_array('after_row_import', $methods)) {
                            $adapter->after_row_import($id);
                        }
                    }
                }
                $iaDb->resetTable();

                if (in_array('after_all_rows_import', $methods)) {
                    $adapter->after_all_rows_import($ids);
                }

                $output['imported'] = count($ids);
                $output['start'] = $data['start'];
                $output['done'] = $data['end'];
            } else {
                $output['error'] = true;
            }
        }
    }

    $iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (iaCore::ACTION_ADD == $pageAction) {
        $error = false;

        $file['path'] = IA_UPLOADS . 'importer/';

        $allowed_ext = array(
            'text/csv' => 'csv',
            'text/xml' => 'xml'
        );

        if (isset($_POST['v']['file']) && !empty($_FILES['file'])) {
            $file['name'] = $_FILES['file']['name'];
            $file['ext'] = substr(strrchr($file['name'], "."), 1);

            if (!$file['name'] || !in_array($file['ext'], $allowed_ext, true)) {
                $error = true;
                $messages[] = iaLanguage::get('incorrect_file');
            }

            if (file_exists($file['path'] . $file['name'])) {
                $error = true;
                $messages[] = iaLanguage::get('file_already_exists');
            }

            if (!$error) {
                @move_uploaded_file($_FILES['file']['tmp_name'], $file['path'] . $file['name']);

                $messages[] = iaLanguage::get('file_uploaded');
                $iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));

                iaUtil::go_to(IA_ADMIN_URL . 'importer/');
            } else {
                $iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
            }
        } elseif (isset($_POST['file_url']) && !empty($_POST['file_url'])) {
            $file['url'] = $_POST['file_url'];
            $file['name'] = substr(strrchr($file['url'], "/"), 1);
            $file['ext'] = substr(strrchr($file['name'], "."), 1);

            if (!$file['name'] || !in_array($file['ext'], $allowed_ext)) {
                $error = true;
                $messages[] = iaLanguage::get('incorrect_file');
            }

            if (file_exists($file['path'] . $file['name'])) {
                $error = true;
                $messages[] = iaLanguage::get('file_already_exists');
            }

            if (!fopen($file['url'], 'r')) {
                $error = true;
                $messages[] = iaLanguage::get('cant_get_file');
            }

            if (!$error) {
                $return = file_put_contents($file['path'] . $file['name'], fopen($file['url'], 'r'));

                if ($return) {
                    $messages[] = iaLanguage::get('file_uploaded');
                    $iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));

                    iaUtil::go_to(IA_ADMIN_URL . 'importer/');
                } else {
                    $error = true;
                    $messages[] = iaLanguage::get('upload_failed');
                    $iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
                }
            } else {
                $iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
            }
        }

        if (!file_exists($file['path'])) {
            mkdir($file['path'], 0755);
        }

        $permissions = is_writable($file['path']);
        if (!$permissions) {
            $permissions = chmod($file['path'], 755);
        }

        $allowed_size = ini_get('upload_max_filesize');
        $iaView->assign('allowed_size', $allowed_size);
        $iaView->assign('permissions', $permissions);

        $iaView->display('upload');
    } else {
        $files = array();
        if (is_dir(IA_UPLOADS . 'importer/')) {
            $files = $iaImporter->listFiles(IA_UPLOADS . 'importer/');
        }
        $iaView->assign('files', $files);

        $adapters = $iaImporter->listFiles(IA_MODULES . 'importer/includes/adapters/');
        $iaView->assign('adapters', $adapters);

        $items = $iaCore->factory('item')->getItems();
        $iaView->assign('items', $items);

        $iaView->display('index');
    }
}
