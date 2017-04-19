<?php

declare(strict_types=1);

namespace NAttreid\FileManager;

use IPub\FlashMessages\FlashNotifier;
use IPub\FlashMessages\Storage\IStorage;
use NAttreid\FileManager\Lang\Translator;
use NAttreid\Form\Form;
use NAttreid\Utils\File;
use NAttreid\Utils\TempFile;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\InvalidArgumentException;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nextras\Application\UI\SecuredLinksControlTrait;
use SplFileInfo;
use stdClass;

/**
 * FileManager
 *
 * @author Attreid <attreid@gmail.com>
 */
class FileManager extends Control
{

	use SecuredLinksControlTrait;

	/**
	 * @var string
	 * @persistent
	 */
	public $path;

	/**
	 * Zakladni adresar
	 * @var string
	 */
	private $basePath;

	/** @var bool */
	private $editable = false;

	/** @var FlashNotifier */
	private $flashNotifier;

	/** @var ITranslator */
	private $translator;

	public function __construct(string $basePath, IStorage $storage)
	{
		parent::__construct();
		$this->flashNotifier = new FlashNotifier(false, $storage);
		if (!Strings::endsWith($basePath, DIRECTORY_SEPARATOR)) {
			$basePath .= DIRECTORY_SEPARATOR;
		}
		$this->basePath = $basePath;
		$this->translator = new Lang\Translator;
	}

	/**
	 * Nastavi translator
	 * @param ITranslator $translator
	 */
	public function setTranslator(ITranslator $translator): void
	{
		$this->translator = $translator;
	}

	/**
	 * Vrati Translator
	 * @return Translator
	 */
	public function getTranslator(): Translator
	{
		return $this->translator;
	}

	/**
	 * Nastavi prava pro editaci
	 * @param bool $editable
	 */
	public function editable(bool $editable = true): void
	{
		$this->editable = $editable;
	}

	/**
	 * Otevreni souboru nebo adresare
	 * @param string $fileName
	 */
	public function handleOpen(string $fileName): void
	{
		if (strpos($fileName, '..' . DIRECTORY_SEPARATOR) !== false) {
			throw new InvalidArgumentException;
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
	 * @param FileInfo $file
	 */
	private function viewFile(FileInfo $file): void
	{
		$this->template->viewFile = $file;
	}

	/**
	 * Zmena adresare
	 * @param string $dir
	 * @throws InvalidArgumentException
	 */
	public function handleChangeDir(string $dir = null): void
	{
		if ($dir !== null) {
			if (
				strpos($dir, '..' . DIRECTORY_SEPARATOR) !== false
				|| !is_dir($this->getBasePath() . $dir)
			) {
				throw new InvalidArgumentException;
			}
		}
		$this->path = $dir;
		$this->redrawControl('fileManagerContainer');
	}

	/**
	 * Velikost souboru
	 * @secured
	 * @param string $fileName
	 */
	public function handleFileSize(string $fileName): void
	{
		if ($this->presenter->isAjax()) {
			$this->template->files = [$this->getFileInfo($fileName, true)];
			$this->redrawControl('itemsContainer');
		} else {
			exit;
		}
	}

	/**
	 * Stahnuti souboru
	 * @secured
	 * @param string $fileName
	 */
	public function handleDownload(string $fileName): void
	{
		$file = $this->getFileInfo($fileName);
		if ($file->isDir) {
			$archive = new TempFile;
			File::zip($this->getFullPath($file->name), (string) $archive);
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
	public function handleFile(string $fileName): void
	{
		$file = $this->getFileInfo($fileName);
		if (strpos($file->type, 'image') !== false) {
			$response = new FileResponse($this->getFullPath($file->name), $file->name, $file->type, false);
			$this->presenter->sendResponse($response);
		} else {
			exit;
		}
	}

	/**
	 * Smazani souboru
	 * @secured
	 * @param string $fileName
	 */
	public function handleDelete(string $fileName): void
	{
		if ($this->presenter->isAjax() && $this->editable) {
			$file = $this->getFileInfo($fileName);
			if ($file->isDir) {
				File::removeDir($this->getFullPath($file->name));
			} else {
				unlink($this->getFullPath($file->name));
			}
			$this->redrawControl('fileManagerContainer');
		} else {
			exit;
		}
	}

	/**
	 * Editace souboru
	 * @secured
	 * @param string $fileName
	 */
	public function handleEdit(string $fileName): void
	{
		if ($this->presenter->isAjax() && $this->editable) {
			$file = $this->getFileInfo($fileName);
			if ($file->editable) {
				$this->viewFile($file);
				$form = $this['editForm'];
				$form->setDefaults([
					'id' => $file->name,
					'content' => $file->content
				]);
				$this->template->editFile = true;
			}
			$this->redrawControl('fileManagerContainer');
		} else {
			exit;
		}
	}

	/**
	 * Prejmenovani souboru
	 * @secured
	 * @param string $fileName
	 */
	public function handleRename(string $fileName): void
	{
		if ($this->presenter->isAjax() && $this->editable) {
			$this->template->files = [$this->rename($fileName)];
			$this->redrawControl('itemsContainer');
		} else {
			exit;
		}
	}

	/**
	 * Pridani souboru
	 */
	public function handleAddFile(): void
	{
		if ($this->presenter->isAjax() && $this->editable) {
			$files = $this->getFiles();
			$fileName = $this->generateName('newFile');
			file_put_contents($this->getFullPath($fileName), null);
			array_unshift($files, $this->rename($fileName));
			$this->template->files = $files;
			$this->redrawControl();
		} else {
			exit;
		}
	}

	/**
	 * Pridani adresare
	 */
	public function handleAddDir(): void
	{
		if ($this->presenter->isAjax() && $this->editable) {
			$files = $this->getFiles();
			$dirName = $this->generateName('newDir');
			mkdir($this->getFullPath($dirName));
			array_unshift($files, $this->rename($dirName));
			$this->template->files = $files;
			$this->redrawControl();
		} else {
			exit;
		}
	}

	/**
	 * Formular editace
	 * @return Form
	 */
	protected function createComponentEditForm(): Form
	{
		$form = new Form;
		$form->setAjaxRequest();

		$form->addHidden('id');

		$form->addTextArea('content')
			->setAttribute('autofocus', true);

		$form->addSubmit('save', $this->translator->translate('fileManager.save'));

		$form->onSuccess[] = [$this, 'editFormSucceeded'];

		return $form;
	}

	/**
	 * Zpracovani editace
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function editFormSucceeded(Form $form, ArrayHash $values)
	{
		if ($this->presenter->isAjax() && $this->editable) {
			file_put_contents($this->getFullPath($values->id), $values->content);

			$this->flashNotifier->success($this->translator->translate('fileManager.dataSaved'));
			$this->redrawControl('fileManagerContainer');
		} else {
			exit;
		}
	}

	/**
	 * Formular prejmenovani
	 * @return Form
	 */
	protected function createComponentRenameForm(): Form
	{
		$form = new Form;
		$form->setAjaxRequest();

		$form->addHidden('id');

		$form->addText('name')
			->setAttribute('autofocus', true);

		$form->onSuccess[] = [$this, 'renameFormSucceeded'];

		return $form;
	}

	/**
	 * Prejmenovani souboru/slozky
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function renameFormSucceeded(Form $form, ArrayHash $values)
	{
		if ($this->presenter->isAjax() && $this->editable) {
			rename($this->getFullPath($values->id), $this->getFullPath($values->name));

			$this->redrawControl('fileManagerContainer');
		} else {
			exit;
		}
	}

	public function render(): void
	{
		$this->template->addFilter('translate', [$this->translator, 'translate']);

		if (!isset($this->template->files)) {
			$this->template->files = $this->getFiles();
		}

		$this->template->path = [];
		$link = '';
		if ($this->path !== null) {
			foreach ($this->getPath() as $dir) {
				$obj = new stdClass;
				$obj->name = $dir;
				$obj->link = $link .= (empty($link) ? '' : DIRECTORY_SEPARATOR) . $dir;
				$this->template->path[] = $obj;
			}
		}

		$this->template->componentId = $this->getUniqueId();
		$this->template->editable = $this->editable;

		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'fileManager.latte');
		$this->template->render();
	}

	/**
	 * Nastavi prejmenovani
	 * @param string $fileName
	 * @return FileInfo
	 */
	private function rename(string $fileName): FileInfo
	{
		$file = $this->getFileInfo($fileName);
		$file->rename = true;

		$form = $this['renameForm'];
		$form->setDefaults([
			'name' => $file->name,
			'id' => $file->name
		]);
		return $file;
	}

	/**
	 * Vrati pole cesty
	 * @return string[]
	 */
	private function getPath(): array
	{
		if ($this->path) {
			return explode(DIRECTORY_SEPARATOR, $this->path);
		}
		return [];
	}

	/**
	 * Vrati Korenovy adresar
	 * @return string
	 * @throws InvalidArgumentException
	 */
	private function getBasePath(): string
	{
		if ($this->basePath === null) {
			throw new InvalidArgumentException('Method setBasePath($path) does not call');
		}
		return $this->basePath;
	}

	/**
	 * Nastavi cestu
	 * @param array $dirs
	 */
	private function setPath(array $dirs): void
	{
		$this->path = implode(DIRECTORY_SEPARATOR, $dirs);
	}

	/**
	 * Vrati cestu
	 * @param string $file
	 * @return string
	 */
	private function getFullPath(string $file = null): string
	{
		return $this->getBasePath() . $this->path . ($file !== null ? DIRECTORY_SEPARATOR . $file : '');
	}

	/**
	 * Vrati polozky v adresari
	 * @return FileInfo[]
	 */
	private function getFiles(): array
	{
		$result = [];
		$files = Finder::find('*')
			->in($this->getFullPath());
		foreach ($files as $file) {
			$result[] = new FileInfo($file);
		}
		usort($result, function ($a, $b) {
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
	 * @param bool $withSize
	 * @return FileInfo
	 */
	private function getFileInfo(string $fileName, bool $withSize = false): FileInfo
	{
		$file = new SplFileInfo($this->getFullPath($fileName));
		return new FileInfo($file, $withSize);
	}

	/**
	 * Generuje nazev
	 * @param string $name
	 * @param int $sufix
	 * @return string
	 */
	private function generateName(string $name, int $sufix = null): string
	{
		$fileName = $name . $sufix;
		if (file_exists($this->getFullPath($fileName))) {
			return $this->generateName($name, $sufix === null ? 1 : ++$sufix);
		}
		return $fileName;
	}
}

interface IFileManagerFactory
{
	public function create(string $basePath): FileManager;
}
