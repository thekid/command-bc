BC for xp-framework/command 8.0+
================================

[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.5+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_5plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_4plus.png)](http://hhvm.com/)

Restores method injection removed as part of xp-framework/command 8.0 in [pull request #7](https://github.com/xp-framework/command/pull/7).

How to use this
---------------

Add dependency:

```sh
$ composer require thekid/command-bc
```

Use the trait supplied in this package:


```php
<?php namespace com\example\cmd;

class Test extends \util\cmd\Command {
  use \util\cmd\MethodInjection;        // <-- Add this line!

  #[@inject(name= 'sources')]
  public function useConfig(Properties $prop) {
    // ...
  }
}
```

**Done!**