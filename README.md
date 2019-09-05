# Silverstripe Prefix Requirements

## Overview

Adds a file hash prefix to all requirements. Replaces the default ?{mtime} suffix to make the requirements fully cacheable.

Whenever a CSS or Javascript file is changed, a new prefixed version of the file is generated. This makes the files fully cacheable because whenever a change is made a file with a new URL is included in the HTML.

It is recommended to minify CSS files using Grunt or Gulp (or any other technique you might want to use). 

## Requirements

* SilverStripe CMS 4.x

Note: this version is compatible with SilverStripe 4. For SilverStripe 3, please see the [1.x release line](https://github.com/xini/silverstripe-prefix-requirements/tree/1).

## Installation

1. composer require innoweb/silverstripe-prefix-requirements
2. run dev/build (http://www.mysite.com/dev/build?flush=all)

## Usage

The prefixed files are generated and stored in the default `CombinedFilesFolder` of the `Requirements_Backend` class. You can use the following entry in your `config.yml` to specify where the generated files are stored:

```
Requirements_Backend:
  default_combined_files_folder: '_your_folder'
``` 

Default is `ASSETS_DIR . '/_combinedfiles'`.

This module doesn't handle css and js in the CMS. 

Because the files are moved to the configured `CombinedFilesFolder`, please make sure you only use paths relative to the website root for includes in your css and js files. E.g. `/_resources/themes/yourtheme/images/icon.png`, not `../images/icon.png`.
