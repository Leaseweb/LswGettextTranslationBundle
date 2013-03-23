LswGettextTranslationBundle
=============

The LswGettextTranslationBundle adds gettext translation support to your Symfony2
application. It is aimed to be faster and more user-friendly than the built-in 
translation support of Symfony2.

## Requirements

* PHP 5.3 with gettext support
* Symfony 2.1 (works under Symfony 2.0 as well)

## Installation

Installation is broken down in the following steps:

1. Download LswGettextTranslationBundle using composer
2. Enable the Bundle
3. Install the needed locales
4. Set the language in your application

### Step 1: Download LswGettextTranslationBundle using composer

Add LswGettextTranslationBundle in your composer.json:

```js
{
    "require": {
        "leaseweb/gettext-translation-bundle": "1.0.*@dev"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update leaseweb/gettext-translation-bundle
```

Composer will install the bundle to your project's `vendor/leaseweb` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Lsw\GettextTranslationBundle\LswGettextTranslationBundle(),
    );
}
```

### Step 3: Install the needed locales

As described [here](https://help.ubuntu.com/community/Locale) you can list the locales
that you have installed on your (Linux) system with the command 'locale -a'. If you want
to support Dutch, English and German you should execute the following commands:

```
sudo locale-gen nl_NL.UTF-8
sudo locale-gen en_US.UTF-8
sudo locale-gen de_DE.UTF-8
````

More language codes can be found [here](http://lh.2xlibre.net/locales/)

### Step 4: Set the language in your application

Use the standard `$request->setLocale('en');` to set the locale in your application.
You should use `$session->setLocale('en');` when you are still using Symfony 2.0 (<2.1).

Edit the following file to define 2 letter shortcuts for the locales (this is recommended):

```
Lsw/GettextTranslationBundle/Resources/config/config.yml
```

## Usage

Usage is broken down in the following steps:

1. Use gettext (convenience) functions in your code
2. Extract the strings from a bundle that need to translated by gettext (.pot file)
3. (First time only) Initialize the languages you want to support in the bundle (.po file)
4. (Skip first time) Update the language (.po) files with the new gettext template (.pot) file
5. Translate the language files using the excellent [Poedit](http://www.poedit.net/) application
6. Combine all translations into one file (.mo file)

### Step 1: Use gettext (convenience) functions in your code

You can use the following functions:

* `_($text)` Shortcut for [gettext](http://php.net/manual/en/function.gettext.php)
* `_n($textSingular,$textPlural,$n)` Shortcut for [ngettext](http://php.net/manual/en/function.ngettext.php)
* `__($format,$args,...)` Shortcut for `sprintf(_($format),$args,...))`
* `__n($formatSingular,$formatPlural,$n,$args,...)` Shortcut for `sprintf(_n($formatSingular,$formatPlural,$n),$args,...))`

### Step 2: Extract the strings from a bundle

Use the `./app/console gettext:bundle:extract` command to search a bundle for translation
strings and to store them into a gettext template (.pot) file.

### Step 3: (First time only) Initialize the languages you want to support

Use the `./app/console gettext:bundle:initialize` command to copy the gettext template (.pot) 
file into the language specific (.po) files.

### Step 4: (Skip first time) Update the gettext language (.po) files with the template

Use the [Poedit](http://www.poedit.net/) application to load a gettext language (.po) file. Choose the 
"Update from template" option and point Poedit to the generated gettext template (.pot) file. Review 
and confirm the changes.

### Step 5: Translate the language files using the excellent [Poedit](http://www.poedit.net/) application

Use the [Poedit](http://www.poedit.net/) application to load a gettext language (.po) file.
Translate all missing strings (shown in blue) and check and correct all fuzzy translated strings
(shown in yellow).

### Step 6: Combine all translation

Use the `./app/console gettext:combine` command combine all gettext language (.po) files into one
compiled gettext (.mo) file.
