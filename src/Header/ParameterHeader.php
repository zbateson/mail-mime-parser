<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\Consumer\IConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService;
use ZBateson\MailMimeParser\Header\Part\NameValuePart;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Represents a header containing an optional main value part and subsequent
 * name/value pairs.
 *
 * If header doesn't contain a non-parameterized 'main' value part, 'getValue()'
 * will return the value of the first parameter.
 *
 * For example: 'Content-Type: text/html; charset=utf-8; name=test.ext'
 *
 * The 'text/html' portion is considered the 'main' value, and 'charset' and
 * 'name' are added as parameterized name/value pairs.
 *
 * With the Autocrypt header, there is no main value portion, for example:
 * 'Autocrypt: addr=zb@example.com; keydata=b64-data'
 *
 * In that example, calling ```php $header->getValue() ``` would return
 * 'zb@example.com', as would calling ```php $header->getValueFor('addr'); ```.
 *
 * @author Zaahid Bateson
 */
class ParameterHeader extends AbstractHeader
{
    /**
     * @var ParameterPart[] key map of lower-case parameter names and associated
     *      ParameterParts.
     */
    protected array $parameters = [];

    public function __construct(
        string $name,
        string $value,
        ?LoggerInterface $logger = null,
        ?ParameterConsumerService $consumerService = null
    ) {
        $di = MailMimeParser::getGlobalContainer();
        parent::__construct(
            $logger ?? $di->get(LoggerInterface::class),
            $consumerService ?? $di->get(ParameterConsumerService::class),
            $name,
            $value
        );
    }

    /**
     * Overridden to assign ParameterParts to a map of lower-case parameter
     * names to ParameterParts.
     */
    protected function parseHeaderValue(IConsumerService $consumer, string $value) : void
    {
        parent::parseHeaderValue($consumer, $value);
        foreach ($this->parts as $part) {
            if ($part instanceof NameValuePart) {
                $this->parameters[\strtolower($part->getName())] = $part;
            }
        }
    }

    /**
     * Returns true if a parameter exists with the passed name.
     *
     * @param string $name The parameter to look up.
     */
    public function hasParameter(string $name) : bool
    {
        return isset($this->parameters[\strtolower($name)]);
    }

    /**
     * Returns the value of the parameter with the given name, or $defaultValue
     * if not set.
     *
     * @param string $name The parameter to retrieve.
     * @param string $defaultValue Optional default value (defaulting to null if
     *        not provided).
     * @return string|null The parameter's value.
     */
    public function getValueFor(string $name, ?string $defaultValue = null) : ?string
    {
        if (!$this->hasParameter($name)) {
            return $defaultValue;
        }
        return $this->parameters[\strtolower($name)]->getValue();
    }
}
