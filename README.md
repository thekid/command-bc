Backwards compatibility for xp-framework/command 8.0+
=====================================================

Add dependency:

```sh
$ composer require thekid/command-bc
```

Use the XPCLI injection trait:


```php
<?php namespace com\example\cmd;

use util\cmd\XPCliInjection;

class Test extends \util\cmd\Command {
  use XPCliInjection;

  #[@inject(name= 'sources')]
  public function useConfig(Properties $prop) {
    // ...
  }
}
```