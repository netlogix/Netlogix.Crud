<?php
namespace Netlogix\Crud\Domain\Model\DataTransfer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Netlogix.Crud".         *
 * It's a forward port of the TYPO3 extension EXT:nxcrudextbase           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Neos\Flow\Annotations as Flow;

/**
 * An UriPointer is used to postpone UriBuilder->for() calls. It contains all necessary
 * information for creating an URL but can be hold as long as no UriBuilder instance
 * is present.
 * Especially for DataTransfer Object objects the UriBuilder should not be a regular property
 * since DataTransfer Objects don't rely on a given ControllerContext but UriBuilders do.
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class UriPointer {

	/**
	 * @var string
	 */
	protected $actionName;

	/**
	 * @var string
	 */
	protected $controllerName;

	/**
	 * @var string
	 */
	protected $packageKey;

	/**
	 * @var string
	 */
	protected $subPackageKey;

	/**
	 * @var string
	 */
	protected $arguments;

	/**
	 * @var mixed
	 */
	protected $inlineRepresentative;

	/**
	 * @param array $properties
	 * @throws \Exception
	 */
	public function __construct(array $properties = []) {
		foreach ($properties as $propertyName => $propertyValue) {
			if (property_exists(get_class($this), $propertyName)) {
				$this->$propertyName = $propertyValue;
			} else {
				throw new \Exception(sprintf('Property "%s" is not an allowed argument for UriPointer.', $propertyName), 1384963286);
			}
		}
	}

	/**
	 * @return string
	 */
	public function getActionName() {
		return $this->actionName;
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @return string
	 */
	public function getControllerName() {
		return $this->controllerName;
	}

	/**
	 * @return string
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * @return integer
	 */
	public function getSubPackageKey() {
		return $this->subPackageKey;
	}

	/**
	 * @return mixed
	 */
	public function getInlineRepresentative() {
		return $this->inlineRepresentative;
	}

}