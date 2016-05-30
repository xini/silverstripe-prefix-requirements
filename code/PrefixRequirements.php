<?php

class Prefix_Requirements_Backend extends Requirements_Backend {
	
	/**
	 * Finds the path for specified file
	 *
	 * @param string $fileOrUrl
	 * @return string|bool
	 */
	protected function path_for_file($fileOrUrl) {
	    // only handle files in themes folder
        if (!Controller::has_curr() || is_a(Controller::curr(), 'LeftAndMain')) {
	        return parent::path_for_file($fileOrUrl);
	    } else {
    		if(preg_match('{^//|http[s]?}', $fileOrUrl)) {
    			return $fileOrUrl;
    		} elseif(Director::fileExists($fileOrUrl)) {
    		    $filePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $fileOrUrl);
    			$baseurl = Director::baseURL();
    			
    			// url parameters
    			if(strpos($fileOrUrl, '?') !== false) {
    				$parameters = '?' . substr($fileOrUrl, strpos($fileOrUrl, '?')+1);
    				$fileOrUrl = substr($fileOrUrl, 0, strpos($fileOrUrl, '?'));
    			} else {
    				$parameters = '';
    			}
    			
    			// get base path
    			$baseFolder = Director::baseFolder();
    			
    			// get combined files folder
    			$combinedFilesFolder = rtrim($this->getCombinedFilesFolder(), '/');
    			if(!file_exists($baseFolder . '/' . $combinedFilesFolder)) {
    				Filesystem::makeFolder($baseFolder . '/' . $combinedFilesFolder);
    			}
    			
    			// get file name
    			$fileName = substr($filePath, strrpos($filePath, '/') + 1);
    			
    			// get prefix
    			$prefix = filemtime($filePath) . '-';
    			$prefixedFilePath = $baseFolder . '/' . $combinedFilesFolder . '/' . $prefix . $fileName;
    			
    			// clean up and create file
    			if (!file_exists($prefixedFilePath)) {
    				// remove old prefixed files
    				foreach (glob($baseFolder . '/' . $combinedFilesFolder . '/' . '[0-9]*-' . $fileName) as $file) {
    					unlink($file);
    				}
    				// copy standard file to prefixed file
    				copy($filePath, $prefixedFilePath);
    			}
    			return "{$baseurl}{$combinedFilesFolder}/{$prefix}{$fileName}{$parameters}";
    		} else {
    			return false;
    		}
	    }
	}
	
}