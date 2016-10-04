<?php

namespace NAttreid\Filemanager\Lang;
use Nette\Localization\ITranslator;

/**
 * Translator
 *
 * @author Attreid <attreid@gmail.com>
 */
class Translator implements ITranslator
{

	private $translations;

	/**
	 * Nastavi jazyk
	 * @param string $lang
	 * @throws \InvalidArgumentException
	 */
	public function setLang($lang)
	{
		if (!$this->translations = @include(__DIR__ . "/$lang.php")) {
			throw new \InvalidArgumentException("Translations for language '$lang' not found.");
		}
	}

	private function getTranslations()
	{
		if ($this->translations === null) {
			$this->setLang('en');
		}
		return $this->translations;
	}

	public function translate($message, $count = null)
	{
		$translations = $this->getTranslations();
		return isset($translations[$message]) ? $translations[$message] : $message;
	}

}
