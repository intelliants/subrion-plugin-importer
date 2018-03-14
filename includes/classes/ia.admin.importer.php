<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

class iaImporter extends abstractModuleAdmin
{
    protected $_adapterInstances = array();


    public function loadAdapter($filename)
    {
        $classFile = IA_MODULES . 'importer' . IA_DS . 'includes' . IA_DS . 'adapters' . IA_DS . $filename;
        $class = explode('.', $filename);
        $className = 'iaAd' . ucfirst($class[0]);

        if (file_exists($classFile)) {
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

        if ($last_pos != filesize($path)) {
            $pos = strrpos($content, PHP_EOL);
            $content = substr($content, 0, $pos);
            $data['end'] = false;
        } else {
            $data['end'] = true;
        }

        $data['start'] = $start + strlen($content);
        $data['rows'] = explode(PHP_EOL, utf8_encode($content));

        return $data;
    }

    public function listFiles($path)
    {
        $files = null;
        $all_files = scandir($path);
        foreach ($all_files as $file) {
            if ($file != "." && $file != "..") {
                $files[] = $file;
            }
        }

        return $files;
    }
}
