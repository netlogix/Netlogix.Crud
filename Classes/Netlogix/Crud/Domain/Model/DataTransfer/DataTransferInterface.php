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

use TYPO3\Flow\Annotations as Flow;

/**
 * Each DataTransfer Object object should implement this interface. It informs the JsonView about
 * properties to be exposed as well as the very object a DataTransfer Object wraps.
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface DataTransferInterface {

	/**
	 * Returns all properties that should be exposed by JsonView
	 *
	 * @return array<string>
	 */
	public function getPropertyNamesToBeApiExposed();

	/**
	 * This object is where this DataTransfer Object is wrapped around. It should *not* be
	 * exposed, because that is the only purpose of this DataTransfer Object object.
	 *
	 * @return Object
	 */
	public function getPayload();

}