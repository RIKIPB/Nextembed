<!--
SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
SPDX-License-Identifier: CC0-1.0
-->

# Nextembed
The Nextembed app for Nextcloud allows users to generate embed codes for their files, making it easy to share content across other websites. With this app, you can create custom embed codes for images, videos, and documents hosted on your Nextcloud instance. This feature enables seamless integration and display of Nextcloud files on external sites without requiring user authentication. Ideal for sharing media and documents publicly or within restricted environments

## Installation

1. Place this app in the `nextcloud/apps/` directory OR `nextcloud/custom_apps/` directory for docker AIO Installs
2. Enable the app through the Nextcloud admin interface.

## Usage
Right click a file and select 'Get Embed Code' from the context menu to copy Embed Code

-------------------------------------------------------------------------

## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder

The make command will install or update Composer dependencies if a composer.json is present and also **npm run build** if a package.json is present in the **js/** folder. The npm **build** script should use local paths for build systems and package managers, so people that simply want to build the app won't need to install npm libraries globally, e.g.:

**package.json**:
```json
"scripts": {
    "test": "node node_modules/gulp-cli/bin/gulp.js karma",
    "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
    "build": "node node_modules/gulp-cli/bin/gulp.js"
}
```


## Publish to App Store

First get an account for the [App Store](http://apps.nextcloud.com/) then run:

    make && make appstore

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.

## Running tests
You can use the provided Makefile to run all tests by using:

    make test

This will run the PHP unit and integration tests and if a package.json is present in the **js/** folder will execute **npm run test**

Of course you can also install [PHPUnit](http://phpunit.de/getting-started.html) and use the configurations directly:

    phpunit -c phpunit.xml

or:

    phpunit -c phpunit.integration.xml

for integration tests
