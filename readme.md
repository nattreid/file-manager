# File manager pro Nette Framework

Nastavení v **config.neon**
```neon
services:
    - 
        implement: \NAttreid\Filemanager\IFileManagerFactory
        arguments: [%appDir%/../]
```

Použití v presenteru
```php
/** @var \NAttreid\Filemanager\IFileManagerFactory @inject */
public $fileManagerFactory;

function createComponentList(){
    $manager = $this->fileManagerFactory->create();
    $manager->editable(true); // povolí editaci, mazání a přejmenování souborů
    return $manager;
}
```

Pokud používáte bower, upravte css
```css
.fileManagerContainer .fileManagerContent .itemContainer a.item .image {
    background-image: url('/images/filemanager/fileIcons.png');
}
```