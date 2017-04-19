<?php

declare(strict_types=1);

namespace NAttreid\FileManager;

use NAttreid\Utils\File;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use SplFileInfo;

/**
 * Class Finfo
 *
 * @property-read string $name Nazev
 * @property-read string $type Typ
 * @property-read float $size Velikost
 * @property-read bool $isDir Je adresar
 * @property-read bool $editable Je mozne soubor editovat
 * @property bool $rename Ma se zapnout prejmenovani
 * @property-read string $content Obsah
 * @property-read DateTime $changed Zmeneno
 * @property-read bool $isImage Zmeneno
 *
 * @author Attreid <attreid@gmail.com>
 */
class FileInfo
{
	use SmartObject;

	/** @var SplFileInfo */
	private $file;

	/** @var string */
	private $type;

	/** @var float */
	private $size;

	/** @var bool */
	private $editable;

	/** @var bool */
	private $rename = false;

	/** @var string */
	private $content;

	/** @var DateTime */
	private $changed;

	/** @var bool */
	private $isImage;

	public function __construct(SplFileInfo $file, bool $withSize = false)
	{
		$this->file = $file;
		$this->size = $withSize ? File::size($file->getPathname()) : null;
		$this->changed = DateTime::createFromFormat('U', filemtime($file->getPathname()));

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$this->type = finfo_file($finfo, $file->getPathname());
		finfo_close($finfo);

		$this->isImage = strpos($this->type, 'image') !== false;

		switch ($this->type) {
			default:
				$this->editable = false;
				break;
			case 'text/plain':
			case 'application/xml':
			case 'text/x-php':
			case 'text/html':
			case 'inode-x-empty':
				$this->editable = true;
				break;
		}
	}

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return $this->file->getFilename();
	}

	/**
	 * @return string
	 */
	protected function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return float|null
	 */
	protected function getSize(): ?float
	{
		return $this->size;
	}

	/**
	 * @return bool
	 */
	protected function isIsDir(): bool
	{
		return $this->file->isDir();
	}

	/**
	 * @return bool
	 */
	protected function isEditable(): bool
	{
		return $this->editable;
	}

	/**
	 * @return bool
	 */
	protected function isRename(): bool
	{
		return $this->rename;
	}

	/**
	 * @param bool $rename
	 */
	protected function setRename(bool $rename): void
	{
		$this->rename = $rename;
	}

	/**
	 * @return string
	 */
	protected function getContent(): string
	{
		if ($this->content === null) {
			$this->content = @file_get_contents($this->file->getRealPath());
		}
		return $this->content;
	}

	/**
	 * @return DateTime
	 */
	protected function getChanged(): DateTime
	{
		return $this->changed;
	}

	/**
	 * @return bool
	 */
	protected function isIsImage(): bool
	{
		return $this->isImage;
	}
}