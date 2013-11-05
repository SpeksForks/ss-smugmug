# Smugmug Service for Silverstripe

The Smugmug Module allows you to access items in your templates via the Smugmug JSON API

## Requirements

*  SilverStripe 3.1
*  An account with Smugmug (http://www.smugmug.com/)
*  An API Key to use with the Smugmug API (http://www.smugmug.com/hack/apikeys)

## Features

*  Display Smugmug Albums and images in your templates using the familiar Silverstripe API
*  Converts albums and images to SS_List, easily editable from the CMS

## Install using composer

```
composer require milkyway/smugmug:*
```

## Setting up

This module is not an out of the box module. To add the ability to add Smugmug Albums to your
page, add the following at the end of your yaml config (mysql/_config/*)

```
Smugmuggable:
  api_key: 'ENTER YOUR API KEY HERE'
  nickname: 'ENTER THE NICKNAME OF YOUR ACCOUNT HERE'
```

To connect it with a page type/data object in the CMS, I suggest using the config (or an extension if you
need to extend the default Smugmuggable_Album data object)

Replace DataObject with the class name of your page type/data object

```
DataObject:
  many_many:
    Albums: 'Smugmuggable_Album'
Smugmuggable_Album:
  belongs_many_many:
    DataObjects: 'DataObject'
```

Note: You will have to add the GridField to manage Smugmug Albums yourself into your dataobject

For ease of use, I have also added extensions which you can use to extend page types that you would
like to include Smugmug Albums.

To use in your templates, the Smugmuggable_Album object has a basic methods called $Images, which you
can iterate through once you reach the Smugmuggable_Album object. The Smugmug Information for a
Smugmug Album can be reached with the method $Info

For all the fields available on an image/album object, please refer to the Smugmug API Documentation:
http://api.smugmug.com/services/api/?version=1.3.0