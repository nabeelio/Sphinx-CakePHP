Updated to further maintain.

Derived version from the Sphinx Behavior by @nabeelio

Updated version for the Sphinx Behavior by Vilen Tambovtsev

I've updated this for Cake2.0, and also so it runs one query to Sphinx,
instead of two (one for results, one for count)

Also makes the search results that are returned from Sphinx available to
the model (I use this to build filters for search)

The original usage page is here:

http://bakery.cakephp.org/articles/xumix/2009/07/11/sphinx-behavior

### Installation

_[Manual]_

* Download this: [https://github.com/nshahzad/Sphinx-CakePHP/archive/master.zip](https://github.com/nshahzad/Sphinx-CakePHP/archive/master.zip)
* Unzip that download.
* Copy the resulting folder to `app/Plugin`
* Rename the folder you just copied to `SphinxCakephp`

_[Composer]_

in the `composer.json` file of your project add
```
"repositories":
[
    {
        "type": "vcs",
        "url": "https://github.com/nabeelio/Sphinx-CakePHP"
    }
],
"require":{
    "nabeelio/sphinx-cakephp": "dev-master"
}
```
The plugin will be installed in your Plugin directory.

### Usage

Make sure the plugin is loaded in `app/Config/bootstrap.php`.

```php
CakePlugin::load('SphinxCakephp');
```
