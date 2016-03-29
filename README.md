# Silverstripe Prefix Requirements

## Overview

Adds a mtime prefix to all requirements. Replaces the default ?{mtime} suffix to make the requirements fully cacheable.

Whenever a CSS or Javascript file is changed, a new prefixed version of the file is generated. This makes the files fully cacheable because whenever a change is made a file with a new URL is included in the HTML.

It is recommended to minify CSS files using Grunt or Gulp (or any other technique you might want to use). 

**Caution:** This module replaces the default `Requirements_Backend` class. Therefor other modules replacing that class like [Minify](https://github.com/nathancox/silverstripe-minify) won't work anymore.

## Requirements

* SilverStripe ~3.1

## Installation

1. composer require xini/silverstripe-prefix-requirements dev-master (or download or git clone the module into a ‘prefix-requirements’ directory in your webroot)
2. run dev/build (http://www.mysite.com/dev/build?flush=all)

## Usage

The prefixed files are generated and stored in the default `CombinedFilesFolder` of the `Requirements` class. You can use the following entry in your _config.php to specify where the generated files are stored:

```
Requirements::set_combined_files_folder({foldername});
``` 

Default is `ASSETS_DIR . '/_combinedfiles'`.
