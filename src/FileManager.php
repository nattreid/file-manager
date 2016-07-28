<?php

namespace NAttreid\Filemanager;

use Nette\Application\UI\Control,
    Nette\Utils\Finder,
    Nette\Application\Responses\FileResponse,
    NAttreid\Utils\File,
    NAttreid\Form\Form,
    NAttreid\Form\IFormFactory,
    IPub\FlashMessages\FlashNotifier;

/**
 * FileManager
 *
 * @author Attreid <attreid@gmail.com>
 */
class FileManager extends Control {

    use \Nextras\Application\UI\SecuredLinksControlTrait;

    /** @persistent */
    public $path;

    /**
     * Zakladni adresar
     * @var string 
     */
    private $basePath;

    /** @var boolean */
    private $editable = FALSE;

    /** @var IFormFactory */
    private $formFactory;

    /** @var FlashNotifier */
    private $flashNotifier;

    public function __construct($basePath, IFormFactory $formFactory, FlashNotifier $flashNotifier) {
        $this->formFactory = $formFactory;
        $this->flashNotifier = $flashNotifier;
        if (!\Nette\Utils\Strings::endsWith($basePath, DIRECTORY_SEPARATOR)) {
            $basePath .= DIRECTORY_SEPARATOR;
        }
        $this->basePath = $basePath;
    }

    /**
     * Nastavi prava pro editaci
     * @param boolean $editable
     */
    public function editable($editable = TRUE) {
        $this->editable = $editable;
    }

    /**
     * Otevreni souboru nebo adresare
     * @param string $fileName
     * @secured
     */
    public function handleOpen($fileName) {
        if (strpos($fileName, '..' . DIRECTORY_SEPARATOR) !== FALSE) {
            throw new \InvalidArgumentException;
        }
        $file = $this->getFileInfo($fileName);
        if ($file->isDir) {
            $path = $this->getPath();
            $path[] = $fileName;
            $this->setPath($path);
        } else {
            $this->viewFile($file);
        }

        $this->redrawControl('fileManagerContainer');
    }

    /**
     * Zobrazeni souboru do okna
     * @param Finfo $file
     */
    private function viewFile($file) {
        if (strpos($file->type, 'image') !== FALSE) {
            $file->type = 'image';
        } else {
            $file->data = @file_get_contents($this->getFullPath($file->name));
        }
        $this->template->viewFile = $file;
    }

    /**
     * Zmena adresare
     * @param string $dir
     * @secured
     * @throws \InvalidArgumentException
     */
    public function handleChangeDir($dir = NULL) {
        if (strpos($dir, '..' . DIRECTORY_SEPARATOR) !== FALSE || !is_dir($this->getBasePath() . $dir)) {
            throw new \InvalidArgumentException;
        }
        $this->path = $dir;
        $this->redrawControl('fileManagerContainer');
    }

    /**
     * Velikost souboru
     * @secured
     * @param string $fileName
     */
    public function handleFileSize($fileName) {
        if ($this->presenter->isAjax()) {
            $this->template->files = [$this->getFileInfo($fileName, TRUE)];
            $this->redrawControl('itemsContainer');
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Stahnuti souboru
     * @secured
     * @param string $fileName
     */
    public function handleDownload($fileName) {
        $file = $this->getFileInfo($fileName);
        if ($file->isDir) {
            $archive = new \nattreid\helpers\TempFile;
            File::zip($this->getFullPath($file->name), $archive);
            $response = new FileResponse($archive, $file->name . '.zip');
        } else {
            $response = new FileResponse($this->getFullPath($file->name));
        }
        $this->presenter->sendResponse($response);
    }

    /**
     * Zobrazeni pouze souboru
     * @secured
     * @param string $fileName
     */
    public function handleFile($fileName) {
        $file = $this->getFileInfo($fileName);
        if (strpos($file->type, 'image') !== FALSE) {
            $response = new FileResponse($this->getFullPath($file->name), $file->name, $file->type, FALSE);
        } else {
            $this->presenter->terminate();
        }
        $this->presenter->sendResponse($response);
    }

    /**
     * Smazani souboru
     * @secured
     * @param string $fileName
     */
    public function handleDelete($fileName) {
        if ($this->presenter->isAjax() && $this->editable) {
            $file = $this->getFileInfo($fileName);
            if ($file->isDir) {
                File::removeDir($this->getFullPath($file->name));
            } else {
                unlink($this->getFullPath($file->name));
            }
            $this->redrawControl('fileManagerContainer');
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Editace souboru
     * @secured
     * @param string $fileName
     */
    public function handleEdit($fileName) {
        if ($this->presenter->isAjax() && $this->editable) {
            $file = $this->getFileInfo($fileName);
            if ($file->editable) {
                $this->viewFile($file);
                $form = $this['editForm'];
                $form->setDefaults([
                    'id' => $file->name,
                    'content' => $file->data
                ]);
                $this->template->editFile = TRUE;
            }
            $this->redrawControl('fileManagerContainer');
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Prejmenovani souboru
     * @secured
     * @param string $fileName
     */
    public function handleRename($fileName) {
        if ($this->presenter->isAjax() && $this->editable) {
            $this->template->files = [$this->rename($fileName)];
            $this->redrawControl('itemsContainer');
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Pridani souboru
     */
    public function handleAddFile() {
        if ($this->presenter->isAjax() && $this->editable) {
            $files = $this->getFiles();
            $fileName = $this->generateName('newFile');
            file_put_contents($this->getFullPath($fileName), NULL);
            array_unshift($files, $this->rename($fileName));
            $this->template->files = $files;
            $this->redrawControl();
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Pridani adresare
     */
    public function handleAddDir() {
        if ($this->presenter->isAjax() && $this->editable) {
            $files = $this->getFiles();
            $dirName = $this->generateName('newDir');
            mkdir($this->getFullPath($dirName));
            array_unshift($files, $this->rename($dirName));
            $this->template->files = $files;
            $this->redrawControl();
        } else {
            $this->presenter->terminate();
        }
    }

    /**
     * Formular editace
     * @return Form
     */
    protected function createComponentEditForm() {
        $form = $this->formFactory->create();
        $form->setAjaxRequest();

        $form->addHidden('id');

        $form->addTextArea('content')
                ->setAttribute('autofocus', TRUE);

        $form->addSubmit('save', 'default.form.save');

        $form->onSuccess[] = function(Form $form, $values) {
            if ($this->presenter->isAjax() && $this->editable) {
                file_put_contents($this->getFullPath($values->id), $values->content);

                $this->flashNotifier->success('default.fileManager.dataSaved');
                $this->redrawControl('fileManagerContainer');
                $this->presenter['flashMessages']->redrawControl();
            } else {
                $this->presenter->terminate();
            }
        };

        return $form;
    }

    /**
     * Formular prejmenovani
     * @return Form
     */
    protected function createComponentRenameForm() {
        $form = $this->formFactory->create();
        $form->setAjaxRequest();

        $form->addHidden('id');

        $form->addText('name')
                ->setAttribute('autofocus', TRUE);

        $form->onSuccess[] = function(Form $form, $values) {
            if ($this->presenter->isAjax() && $this->editable) {
                rename($this->getFullPath($values->id), $this->getFullPath($values->name));

                $this->redrawControl('fileManagerContainer');
            } else {
                $this->presenter->terminate();
            }
        };

        return $form;
    }

    public function render() {
        if (!isset($this->template->files)) {
            $this->template->files = $this->getFiles();
        }

        $this->template->path = [];
        $link = '';
        if ($this->path !== NULL) {
            foreach ($this->getPath() as $dir) {
                $obj = new \stdClass;
                $obj->name = $dir;
                $obj->link = $link .= (empty($link) ? '' : DIRECTORY_SEPARATOR) . $dir;
                $this->template->path[] = $obj;
            }
        }

        $this->template->componentId = $this->getUniqueId();
        $this->template->editable = $this->editable;

        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'fileManager.latte');
        $this->template->render();
    }

    /**
     * Nastavi prejmenovani
     * @param string $fileName
     * @return Finfo
     */
    private function rename($fileName) {
        $file = $this->getFileInfo($fileName);
        $file->rename = TRUE;

        $form = $this['renameForm'];
        $form->setDefaults([
            'name' => $file->name,
            'id' => $file->name
        ]);
        return $file;
    }

    /**
     * Vrati pole cesty
     * @return array
     */
    private function getPath() {
        if ($this->path) {
            return explode(DIRECTORY_SEPARATOR, $this->path);
        }
        return NULL;
    }

    /**
     * Vrati Korenovy adresar
     * @return string
     * @throws \Nette\InvalidArgumentException
     */
    private function getBasePath() {
        if ($this->basePath === NULL) {
            throw new \Nette\InvalidArgumentException('Neni volana metoda setBasePath($path)');
        }
        return $this->basePath;
    }

    /**
     * Nastavi cestu
     * @param array $dirs
     */
    private function setPath(array $dirs) {
        $this->path = implode(DIRECTORY_SEPARATOR, $dirs);
    }

    /**
     * Vrati cestu
     * @param string $file
     * @return string
     */
    private function getFullPath($file = NULL) {
        return $this->getBasePath() . $this->path . ($file !== NULL ? DIRECTORY_SEPARATOR . $file : '');
    }

    /**
     * Vrati polozky v adresari
     * @return array
     */
    private function getFiles() {
        $result = [];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $files = Finder::find('*')
                ->in($this->getFullPath());
        foreach ($files as $file) {
            $result[] = $this->createFileInfo($file, $finfo);
        }
        finfo_close($finfo);
        usort($result, function($a, $b) {
            if ($a->isDir == $b->isDir) {
                return strcmp($a->name, $b->name);
            } else {
                if ($a->isDir) {
                    return -1;
                } else {
                    return 1;
                }
            }
        });
        return $result;
    }

    /**
     * Vrati informace o souboru nebo adresari
     * @param string $fileName
     * @param boolean $size
     * @return Finfo
     */
    private function getFileInfo($fileName, $size = FALSE) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file = $this->createFileInfo(new \SplFileInfo($this->getFullPath($fileName)), $finfo, $size);
        finfo_close($finfo);
        return $file;
    }

    /**
     * Vrati informace o souboru nebo adresariT
     * @param \SplFileInfo $file
     * @param mixed $finfo
     * @param boolean $size
     * @return Finfo
     */
    private function createFileInfo($file, $finfo, $size = FALSE) {
        $obj = new Finfo;
        $obj->name = $file->getFilename();
        $obj->type = finfo_file($finfo, $file->getPathname());
        $obj->size = $size ? File::size($file->getPathname()) : NULL;
        $obj->change = filemtime($file->getPathname());
        $obj->isDir = $file->isDir();
        $obj->rename = FALSE;
        switch ($obj->type) {
            default:
                $obj->editable = FALSE;
                break;
            case 'text/plain':
            case 'application/xml':
            case 'text/x-php':
            case 'text/html':
                $obj->editable = TRUE;
                break;
        }

        return $obj;
    }

    /**
     * Generuje nazev
     * @param string $name
     * @param int $sufix
     * @return string
     */
    private function generateName($name, $sufix = NULL) {
        $fileName = $name . $sufix;
        if (file_exists($this->getFullPath($fileName))) {
            return $this->generateName($name, $sufix === NULL ? 1 : ++$sufix);
        }
        return $fileName;
    }

}

class Finfo {

    /**
     * Nazev
     * @var string 
     */
    public $name;

    /**
     * Typ
     * @var string 
     */
    public $type;

    /**
     * Velikost
     * @var float 
     */
    public $size;

    /**
     * Je adresar
     * @var boolean 
     */
    public $isDir;

    /**
     * Je mozne soubor editovat
     * @var boolean 
     */
    public $editable;

    /**
     * Ma se zapnout prejmenovani
     * @var boolean 
     */
    public $rename;

}

interface IFileManagerFactory {

    /** @return FileManager */
    public function create($basePath);
}
