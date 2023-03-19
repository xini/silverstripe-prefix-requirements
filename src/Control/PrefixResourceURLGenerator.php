<?php

namespace Innoweb\PrefixRequirements\Control;

use InvalidArgumentException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\SimpleResourceURLGenerator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\View\Requirements;
use Webmozart\Glob\Glob;

class PrefixResourceURLGenerator extends SimpleResourceURLGenerator implements ResourceURLGenerator, Flushable {

    use Configurable;

    private $nonceStyle;

    private static $nonce_style;
    private static $use_postfix = false;
    private static $excluded_resources = [];

    public function setNonceStyle($nonceStyle)
    {
        if ($nonceStyle && !in_array($nonceStyle, ['mtime', 'sha1', 'md5'])) {
            throw new InvalidArgumentException("NonceStyle '$nonceStyle' is not supported");
        }
        $this->nonceStyle = $nonceStyle;
        return $this;
    }

    public function getNonceStyle()
    {
        if (($style = Config::inst()->get(static::class, 'nonce_style')) && in_array($style, ['mtime', 'sha1', 'md5'])) {
            return $style;
        }
        if (($style = $this->nonceStyle) && in_array($style, ['mtime', 'sha1', 'md5'])) {
            return $style;
        }
        return null;
    }

    public function isResourceExcludedFromPrefixing($relativePath): bool
    {
        $isExcluded = false;
        $excludedPaths = Config::inst()->get(static::class, 'excluded_resources');
        if (!empty($excludedPaths))
        {
            if ($relativePath instanceof ModuleResource) {
                $relativePath = $relativePath->getRelativePath();
            }
            if (!is_string($relativePath)) {
                return $isExcluded;
            }
            $relativePath = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            foreach ($excludedPaths as $path)
            {
                $path = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $path);
                if (Glob::match($relativePath, $path)) {
                    $isExcluded = true;
                    break;
                }
            }
        }
        return $isExcluded;
    }

    public function urlForResource($relativePath)
    {
        if (!Controller::has_curr() || is_a(Controller::curr(), LeftAndMain::class)) {
            return parent::urlForResource($relativePath);
        }

        $isExcluded = $this->isResourceExcludedFromPrefixing($relativePath);
        if ($isExcluded) {
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
            if (strpos($relativePath ?? '', '?') !== false) {
                list($relativePath, $query) = explode('?', $relativePath ?? '');
            }

            list($exists, $absolutePath, $relativePath) = $this->resolvePublicResource($relativePath);
        }
        if (!$exists) {
            trigger_error("File {$relativePath} does not exist", E_USER_NOTICE);
        }

        // Switch slashes for URL
        $relativeURL = Convert::slashes($relativePath, '/');

        // Apply url rewrites
        $rules = Config::inst()->get(static::class, 'url_rewrites') ?: [];
        foreach ($rules as $from => $to) {
            $relativeURL = preg_replace($from ?? '', $to ?? '', $relativeURL ?? '');
        }

        if ($exists && is_file($absolutePath ?? '')) {

            // get file name
            $pathArr = pathinfo($absolutePath);
            $fileName = $pathArr['filename'];
            $extension = $pathArr['extension'];

            // get prefix
            if ($nonceStyle = $this->getNonceStyle()) {
                switch ($nonceStyle) {
                    case 'mtime':
                        $method = 'filemtime';
                        break;
                    case 'sha1':
                        $method = 'sha1_file';
                        break;
                    case 'md5':
                        $method = 'md5_file';
                        break;
                }
                $prefix = call_user_func($method, $absolutePath);
            } else {
                $prefix = base_convert(md5_file($absolutePath), 16, 36);
            }

            // get combined files folder, prefix file name and put in folder
            $assetHandler = Requirements::backend()->getAssetHandler();
            $combinedFilesFolder = Requirements::backend()->getCombinedFilesFolder();

            $newFileName = $prefix . '-' . $fileName . '.' . $extension;
            if (self::config()->use_postfix) {
                $newFileName = $fileName . '-' . $prefix . '.' . $extension;
            }

            $prefixedFilePath = $assetHandler->getContentURL(
                File::join_paths(
                    $combinedFilesFolder,
                    $newFileName
                ),
                function() use ($absolutePath) {
                    return file_get_contents($absolutePath);
                }
            );

            // build url to prefixed file in combined files folder
            $url = Controller::join_links(
                Director::baseURL(),
                $prefixedFilePath
            );

        } else {

            $url = Controller::join_links(
                Director::baseURL(),
                $relativeURL
            );
        }

        // Add back querystring
        if ($query) {
            $url .= '?' . $query;
        }

        return $url;
    }

    public static function flush() {
        $assetHandler = Requirements::backend()->getAssetHandler();
        $combinedFilesFolder = Requirements::backend()->getCombinedFilesFolder();

        // remove old prefixed files
        $filesystem = $assetHandler->getFilesystem();
        $combinedFilesFolderContents = $filesystem->listContents($combinedFilesFolder);
        foreach ($combinedFilesFolderContents as $item) {
            $itemFileName = $item['basename'] ?? '';
            if (preg_match('/([a-z0-9]{10,32}-.*|.*-[a-z0-9]{10,32})\.(js|css)/', $itemFileName)) {
                $assetHandler->removeContent(
                    File::join_paths(
                        $combinedFilesFolder,
                        $itemFileName
                    )
                );
            }
        }
    }
}
