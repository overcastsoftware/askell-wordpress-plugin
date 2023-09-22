# Askell for WordPress

## Build

For a one-off build, the following can be run:

```sh
composer install
npm install
npm run build
```

This builds the JavaScript and CSS files for the project and adds them to the
`./build` directory.

## Development

### Live Build

For a live build process that watches for file changes, you can keep
`npm run start` running while developing for a constant build process.

### Frontend linting

JS and Sass files can be linted using `npm run lint:js` and `npm run lint:css`.

### Linting PHP Code

The PHP file can be linted using PHPCS. After running `composer install`, the
WordPress Coding standards should be installed as well.

Run `./vendor/bin/phpcs askell-registration.php --standard=WordPress` to lint
the code and `./vendor/bin/phpcbf askell-registration.php --standard=WordPress`
respectively to fix the linting errors and warnings automatically.

## Package and release

Don't forget to update version number in the readme.txt, askell-registration.php
and block.json files before creating a new release of the plugin.

When the version number has been bumped up, run the commands from the Build
section in addition to:

```sh
npm run plugin-zip
```

This creates a zip-file located in the project root, which then can be installed
to WordPress directly.
