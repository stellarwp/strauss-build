# StellarWP Strauss Build

This is a helper repository for handling Strauss builds.

## Installation

```bash
composer require stellarwp/strauss-build
```

## Configuration

The `composer.json` file should contain the following:

```json
"scripts": {
	"strauss": [
		"./vendor/bin/stellar-strauss"
	],
	"post-install-cmd": [
		"@strauss --command=install"
	],
	"post-update-cmd": [
		"@strauss --command=update"
	]
},
```
