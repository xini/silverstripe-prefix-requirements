<?php

class Prefix_Requirements_Backend extends Requirements_Backend {
	
	/**
	 * Disabled, doesn't do anything anyway.
	 */
	protected $suffix_requirements = false;
	
	/**
	 * Finds the path for specified file
	 *
	 * @param string $fileOrUrl
	 * @return string|bool
	 */
	protected function path_for_file($fileOrUrl) {
		if(preg_match('{^//|http[s]?}', $fileOrUrl)) {
			return $fileOrUrl;
		} elseif(Director::fileExists($fileOrUrl)) {
			$baseurl = Director::baseURL();
			// url parameters
			if(strpos($fileOrUrl, '?') !== false) {
				$suffix = '?';
				$suffix .= substr($fileOrUrl, strpos($fileOrUrl, '?')+1);
				$fileOrUrl = substr($fileOrUrl, 0, strpos($fileOrUrl, '?'));
			} else {
				$suffix = '';
			}
			// get folders and path
			$basePath = Director::baseFolder() . '/';
			$combinedFilesFolder = ($this->getCombinedFilesFolder()) ? ($this->getCombinedFilesFolder()) : '';
			$combinedFilesFolder = trim($combinedFilesFolder, '/');
			$combinedFilesFolderPath = $combinedFilesFolder . '/';
			// Make the folder if necessary
			if(!file_exists($basePath . $combinedFilesFolder)) {
				Filesystem::makeFolder($basePath . $combinedFilesFolder);
			}
			// split path and file
			$filepath = substr($fileOrUrl, 0, strrpos($fileOrUrl, '/')) . '/';
			$filename = substr($fileOrUrl, strrpos($fileOrUrl, '/') + 1);
			// get prefix
			$prefix = filemtime($basePath . $filepath . $filename) . '-';
			$prefixedFilePath = $basePath . $combinedFilesFolderPath . $prefix . $filename;
			if (!file_exists($prefixedFilePath)) {
				// remove old prefixed files
				foreach (glob($basePath . $combinedFilesFolderPath . '[0-9]*-' . $filename) as $file) {
					unlink($file);
				}
				// copy standard file to prefixed file
				copy($basePath . $filepath . $filename, $prefixedFilePath);
			}
			return "{$baseurl}{$combinedFilesFolderPath}{$prefix}{$filename}{$suffix}";
		} else {
			return false;
		}
	}
	
}