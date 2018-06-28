<?php

namespace Innoweb\PrefixRequirements\Control;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\SimpleResourceURLGenerator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\View\Requirements;

class PrefixResourceURLGenerator extends SimpleResourceURLGenerator implements ResourceURLGenerator {

    public function urlForResource($relativePath)
    {
        if (!Controller::has_curr() || is_a(Controller::curr(), LeftAndMain::class)) {
            return parent::urlForResource($relativePath);
        }

        $query = '';
        if ($relativePath instanceof ModuleResource) {
            list($exists, $absolutePath, $relativePath) = $this->resolveModuleResource($relativePath);
        } elseif (Director::is_absolute_url($relativePath)) {
            // Path is not relative, and probably not of this site
            return $relativePath;
        } else {
            // Save querystring for later
            if (strpos($relativePath, '?') !== false) {
                list($relativePath, $query) = explode('?', $relativePath);
            }

            // Determine lookup mechanism based on existence of public/ folder.
            // From 5.0 onwards only resolvePublicResource() will be used.
            if (!Director::publicDir()) {
                list($exists, $absolutePath, $relativePath) = $this->resolveUnsecuredResource($relativePath);
            } else {
                list($exists, $absolutePath, $relativePath) = $this->resolvePublicResource($relativePath);
            }
        }
        if (!$exists) {
            trigger_error("File {$relativePath} does not exist", E_USER_NOTICE);
        }

        // Switch slashes for URL
        $relativeURL = Convert::slashes($relativePath, '/');

        // Apply url rewrites
        $rules = Config::inst()->get(static::class, 'url_rewrites') ?: [];
        foreach ($rules as $from => $to) {
            $relativeURL = preg_replace($from, $to, $relativeURL);
        }

        // get file name
        $fileName = substr($absolutePath, strrpos($absolutePath, '/') + 1);

        // get prefix
        $prefix = filemtime($absolutePath) . '-';

        // get combined files folder, prefix file name and put in folder
        $assetHandler = Requirements::backend()->getAssetHandler();
        $combinedFilesFolder = Requirements::backend()->getCombinedFilesFolder();

        $prefixedFilePath = $assetHandler->getContentURL(
            File::join_paths(
                $combinedFilesFolder,
                $prefix . $fileName
            ),
            function() use ($absolutePath) {
                return file_get_contents($absolutePath);
            }
        );

        // remove old prefixed files
        $filesystem = $assetHandler->getFilesystem();
        $combinedFilesFolderContents = $filesystem->listContents($combinedFilesFolder);
        foreach ($combinedFilesFolderContents as $item) {
            $itemFileName = $item['basename'];
            if ($itemFileName !== $prefix . $fileName) {
                if (preg_match('/[0-9]*-' . $fileName . '/', $itemFileName)) {
                    $assetHandler->removeContent(
                        File::join_paths(
                            $combinedFilesFolder,
                            $itemFileName
                        )
                    );
                }
            }
        }

        // build url to prefixed file in combined files folder
        $url = Controller::join_links(
            Director::baseURL(),
            $prefixedFilePath
        );

        // Add back querystring
        if ($query) {
            $url .= '?' . $query;
        }

        return $url;
    }
}