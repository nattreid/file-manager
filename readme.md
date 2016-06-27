# File manager pro Nette Framework

Nastavení v **config.neon**
```neon
services:
    - \NAttreid\Filemanager\IFileManagerFactory
```

Pokud používáte bower, upravte css
```css
.fileManagerContainer .fileManagerContent .itemContainer a.item .image {
    background-image: url('/images/filemanager/fileIcons.png');
}
```

Použití v presenteru
```php
/** @var \NAttreid\Filemanager\IFileManagerFactory @inject */
public $fileManagerFactory;

function createComponentList(){
    $manager = $this->fileManagerFactory->create('baseRoot');
    $manager->editable(true); // povolí editaci, mazání a přejmenování souborů
    return $manager;
}
```