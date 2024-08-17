# Data Document Backup Library

Extensible PHP library to export domain data into a formatted document for backup purposes and placed into remote storage.

It is made for use-cases where mission-critical data needs to be accessible through a secondary channel near-instantly even if the primary infrastructure has an outage.

The library is designed to be used with the Symfony Messenger, but the CreateDocument command handler can be invoked directly. Optionally, the implementation can be provided with a PSR-3 Logger.

Most document generation strategies will need to include a template from the filesystem. Please ensure the template path is not user-provided or whitelisted and your PHP installation protects which files can be accessed.

** Requires temporary files ** Please ensure your system allows the generation of temporary files.

## Get started

Include the library into your project and configure your framework / DI-container to provision the CreateDocumentHandler as service or message handler.

Use the CreateDocument command to gather all details required to generate a document.

Generating documents generally requires many dependencies, php libraries or external binaries. To reduce pollution of your vendor folder and containers, you may want to create a separate worker application, connected to the main application through a message bus.

## Supported Document Generation Strategies

### Twig + Chromium
Using the twig templating language to generate HTML and headless chromium to transform it into a PDF. Easiest to create good looking PDFs, but requires authoring CSS & HTML.
You can either provide a template file or provide a dynamic template in the TemplateReference config `template` property.

** Requires installation of composer packages: `twig/twig` and `chrome-php/chrome` as well as a chromium binary within the system path. **
When deciding on your strategy, keep in mind that chromium (within Alpine Docker) adds 600+ MB to your image.

### Word Template: Microsoft Office Open XML (.docx)
Use a MS Word document with placeholders `e.g. ${name}` to generate a MS Word document. This uses the PHPOffice/PHPWord template processing capability. At this time, only simple value replacements are possible, support for block and row cloning is planned.

This strategy does not allow for inclusion of images into the document.

In theory, other output formats are possible through PHPWord, however PDF support is very limited. PDF conversion could be accomplished through a third party tool processing incoming files at the remote storage location.

** Requires installation of composer package `phpoffice/phpword` and one of 'mpdf',  'tcpdf', 'dompdf' **

### Custom
You may create a custom strategy by implementing the DocumentGenerator interface and providing it to the CreateDocumentHandler.

## Storage
For each document generation, a backup target has to be defined. This includes the filename, storage type and the type-specific storage configuration.

### Local
Files may be stored locally

Storage Configuration:
````php
[
   'root' => '/root/path/', // required
]
````
### WebDAV
Storage Configuration:
````php
[
    'baseUri' => 'http://your-webdav-server.org/', // required
    'userName' => 'your_user',
    'password' => 'superSecret1234'
]
````
### FTP

Storage Configuration:
````php
[
   'host' => 'hostname', // required
   'root' => '/root/path/', // required
   'username' => 'username', // required
   'password' => 'password', // required
   'port' => 21,
   'ssl' => false,
   'timeout' => 90,
   'utf8' => false,
   'passive' => true,
   'transferMode' => FTP_BINARY,
   'systemType' => null, // 'windows' or 'unix'
   'ignorePassiveAddress' => null, // true or false
   'timestampsOnUnixListingsEnabled' => false, // true or false
   'recurseManually' => true // true
]
````

### Custom
If you need another storage system, please check the official phpleague/flysystem adapters. If an adapter exists (very likely), please create a pull request to extend the flysystem adapter in this library to allow instantiation of the adapter.

If your needs are not covered by flysystem, you can implement your own Version of BackupFilesystem and provide it to the CreateDocumentHandler.


## Template Design

**Note:** Keys in the Data object of a CreateDocument command MUST NOT start with _ (reserved for provider specific template variables).

## Development

The library includes a Dockerfile to generate an image with all required tooling. All regular commands are aliased in the composer.json.

### Building the included docker image

Note: If you want to create your own image to use the library, check the build of the worker application for inspiration

Requires writable directories for temporary files and template caching.

````docker build -t php-tools-chromium:8.3 -f Dockerfile .````
