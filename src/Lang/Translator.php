<?php

declare(strict_types=1);

namespace NAttreid\FileManager\Lang;

use InvalidArgumentException;
use Nette\Localization\ITranslator;

/**
 * Translator
 *
 * @author Attreid <attreid@gmail.com>
 */
class Translator implements ITranslator
{

	/** @var string[] */
	private $translations;

	/**
	 * Nastavi jazyk
	 * @param string $lang
	 * @throws InvalidArgumentException
	 */
	public function setLang(string $lang): void
	{
		if (!$this->translations = @include(__DIR__ . "/$lang.php")) {
			throw new InvalidArgumentException("Translations for language '$lang' not found.");
		}
	}

	private function getTranslations(): array
	{
		if ($this->translations === null) {
			$this->setLang('en');
		}
		return $this->translations;
	}

	public function translate($message, $count = null)
	{
		$translation = $this->getTranslations();

		$arr = explode('.', $message);
		foreach ($arr as $item) {
			if (!isset($translation[$item])) {
				return $message;
			}
			$translation = $translation[$item];
		}
		if (is_array($translation)) {
			return $message;
		}
		return $translation;
	}

}
