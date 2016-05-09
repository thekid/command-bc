Backwards compatibility for xp-framework/command 8.0+
-----------------------------------------------------

Add dependency:

```sh
$ composer require thekid/command-bc
```

Use the XPCLI injection trait:


```php
<?php namespace com\example\cmd;

class Test extends \util\cmd\Command {
  use \util\cmd\XPCliInjection;        // <-- Add this line!

  #[@inject(name= 'sources')]
  public function useConfig(Properties $prop) {
    // ...
  }
}
```