<?php
namespace Netlogix\Crud\Http\Request;

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
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * Sets the request format based on the HTTP "accept" header.
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class SetFormatByAcceptHeaderAspect {

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\ActionRequest->setArguments())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function setRequestFormatByAcceptHeader(JoinPointInterface $joinPoint) {

		$joinPoint->getAdviceChain()->proceed($joinPoint);
		/** @var \TYPO3\Flow\Mvc\ActionRequest $actionRequest */
		$actionRequest = $joinPoint->getProxy();
		if ($actionRequest->getFormat() === NULL) {
			foreach (array('Accept', 'Http-Accept') as $acceptFieldNameOptions) {
				if (strpos($actionRequest->getHttpRequest()->getHeader($acceptFieldNameOptions), 'application/json') !== FALSE) {
					$actionRequest->setFormat('json');
				}
			}
		}

	}

}