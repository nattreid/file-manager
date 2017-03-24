# File manager pro Nette Framework

Nastavení v **config.neon**
```neon
services:
    - NAttreid\Filemanager\IFileManagerFactory
```

Pokud používáte bower, upravte css
```css
.fileManagerContainer .fileManagerContent .itemContainer a.item .image {
    background-image: url('/images/filemanager/fileIcons.png');
}
```

Použití v presenteru
```php
/** @var \NAttreid\FileManager\IFileManagerFactory @inject */
public $fileManagerFactory;

function createComponentList(){
    $basePath = 'korenovyAdresar';
    $manager = $this->fileManagerFactory->create($basePath);
    $manager->editable(true); // povolí editaci, mazání a přejmenování souborů

    // pro zmenu jazyka
    $manager->getTranslator()->setLang('cs');

    return $manager;
}
```