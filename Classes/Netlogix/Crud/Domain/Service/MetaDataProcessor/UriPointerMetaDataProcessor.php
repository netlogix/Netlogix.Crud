<?php
namespace Netlogix\Crud\Domain\Service\MetaDataProcessor;

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
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * Enhances Json output by some meta data
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class UriPointerMetaDataProcessor extends AbstractMetaDataProcessor {

	/**
	 * @var Browser
	 * @Flow\Inject
	 */
	protected $browser;

	/**
	 * @var CurlEngine
	 * @Flow\Inject
	 */
	protected $browserRequestEngine;

	/**
	 * Enhances the metaData property by additional data.
	 *
	 * Additional information can be any kind of data that is necessary for the client
	 * to process its model but does not belong to the domain data. Those can be e.g.
	 * the "readOnly" attribute or pre-fetched content of an Pointer.
	 *
	 * Fetching response data including response headers doesn't work without curl
	 * because request headers aren't attached properly to the non-curl request message
	 * string. This makes sure to have curl enabled for at least this request. It's
	 * stored and resetted afterwards.
	 *
	 * @param array $metaData
	 * @param string $propertyPath
	 * @param mixed $processedValue
	 * @param array $alreadyIncluded
	 * @param mixed $object
	 * @param UriBuilder $uriBuilder
	 * @param string $metaDataProcessorGroup
	 * @return array
	 */
	public function process($metaData, $propertyPath, $processedValue, array &$alreadyIncluded, $object, $uriBuilder, $metaDataProcessorGroup) {
		if (in_array($processedValue, $alreadyIncluded)) {
			return $metaData;
		}

		$this->browser->setRequestEngine($this->browserRequestEngine);

		$uri = new Uri($processedValue);

		$request = Request::create($uri);
		$request->getUri()->setUsername($uri->getUsername());
		$request->getUri()->setPassword($uri->getPassword());
		$request->setHeader('Accept', 'application/json, text/plain, */*');

		$response = $this->browser->sendRequest($request);

		if ($response->getStatusCode() === 200) {
			$content = json_decode((string)$response, TRUE);

			/*
			 * Workaround! When TYPO3 caches results, the returned status code isn't
			 * part of the cached data. So on a cachable page, the initial first load
			 * might have some error codes like "400 - bad request", but all second
			 * requests are answered with the very return body indicating errors but
			 * a "200 - OK".
			 */
			if (!(isset($content['errors']) && isset($content['resourceArgumentName']) && count($content) === 2)) {
				$metaData['content'] = $content;
				if (!$metaData['content']) {
					unset($metaData['content']);
				}
			}
		}

		$alreadyIncluded[] = $processedValue;

		return $metaData;
	}

}