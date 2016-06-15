#File manager pro Nette Framework

Nastavení v **config.neon**
```neon
services:
    - 
        implement: nattreid\filemanager\IFileManagerFactory
        arguments: [%appDir%/../]
```

Použití v presenteru
```php
/** @var \nattreid\filemanager\IFileManagerFactory @inject */
public $fileManagerFactory;

function createComponentList(){
    $manager = $this->fileManagerFactory->create();
    $manager->editable(true); // povolí editaci, mazání a přejmenování souborů
    return $manager;
}
```