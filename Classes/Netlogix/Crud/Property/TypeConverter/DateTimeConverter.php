<?php
namespace Netlogix\Crud\Property\TypeConverter;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DateTimeConverter extends \TYPO3\Flow\Property\TypeConverter\DateTimeConverter {

	/**
	 * @var integer
	 */
	protected $priority = 100;

	/**
	 * Converts $source to a \DateTime using the configured dateFormat
	 *
	 * @param string|integer|array $source the string to be converted to a \DateTime object
	 * @param string $targetType must be "DateTime"
	 * @param array $convertedChildProperties not used currently
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \DateTime
	 * @throws \TYPO3\Flow\Validation\Error
	 * @throws \Exception
	 * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$date = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
		if ($date instanceof \TYPO3\Flow\Validation\Error && is_string($source)) {
			$newDate = new $targetType($source);
			if ($newDate !== FALSE) {
				$timeZone = (new $targetType)->getTimezone();
				$newDate->setTimezone($timeZone);
				$date = $newDate;
			}
		}

		return $date;
	}

}