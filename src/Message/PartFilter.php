<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use InvalidArgumentException;

/**
 * Description of PartFilter
 *
 * @author Zaahid Bateson
 */
class PartFilter
{
    const FILTER_OFF = 1;
    const FILTER_EXCLUDE = 2;
    const FILTER_INCLUDE = 3;
    
    private $multipart = PartFilter::FILTER_OFF;
    
    private $textpart = PartFilter::FILTER_OFF;
    
    private $signedpart = PartFilter::FILTER_EXCLUDE;
    
    private $headers = [];
    
    public static function fromContentType($contentType)
    {
        return new static([
            'headers' => [
                static::FILTER_INCLUDE => [
                    'Content-Type' => $contentType
                ]
            ]
        ]);
    }
    
    public static function fromInlineContentType($contentType)
    {
        return new static([
            'headers' => [
                static::FILTER_INCLUDE => [
                    'Content-Type' => $contentType
                ],
                static::FILTER_EXCLUDE => [
                    'Content-Disposition' => 'attachment'
                ]
            ]
        ]);
    }
    
    public static function fromDisposition($disposition, $multipart = PartFilter::FILTER_OFF)
    {
        return new static([
            'multipart' => $multipart,
            'headers' => [
                static::FILTER_INCLUDE => [
                    'Content-Disposition' => $disposition
                ]
            ]
        ]);
    }
    
    public function __construct(array $filter = [])
    {
        $params = [ 'multipart', 'textpart', 'signedpart', 'headers' ];
        foreach ($params as $param) {
            if (isset($filter[$param])) {
                $this->__set($param, $filter[$param]);
            }
        }
    }
    
    private function validateArgument($name, $value, array $valid)
    {
        if (!in_array($value, $valid)) {
            $last = array_pop($valid);
            throw new InvalidArgumentException(
                '$value parameter for ' . $name . ' must be one of '
                . join(', ', $valid) . ' or ' . $last . ' - "' . $value
                . '" provided'
            );
        }
    }
    
    public function __set($name, $value)
    {
        if ($name === 'multipart' || $name === 'textpart' || $name === 'signedpart') {
            $this->validateArgument(
                $name,
                $value,
                [ static::FILTER_OFF, static::FILTER_EXCLUDE, static::FILTER_INCLUDE ]
            );
            $this->$name = $value;
        } elseif ($name === 'headers') {
            if (!is_array($value)) {
                throw new InvalidArgumentException('$value must be an array');
            }
            array_walk($value, function ($v, $k) {
                $this->validateArgument(
                    'headers',
                    $k,
                    [ static::FILTER_EXCLUDE, static::FILTER_INCLUDE ]
                );
                if (!is_array($v)) {
                    throw new InvalidArgumentException(
                        '$value must be an array with keys set to FILTER_EXCLUDE, '
                        . 'FILTER_INCLUDE and values set to an array of header '
                        . 'name => values'
                    );
                }
            });
            $this->$name = $value;
        }
    }
    
    public function __isset($name)
    {
        return isset($this->$name);
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    public function filter(MimePart $part)
    {
        if (($this->multipart === static::FILTER_EXCLUDE && $part->isMultiPart())
            || ($this->multipart === static::FILTER_INCLUDE && !$part->isMultiPart())) {
            return false;
        } elseif (($this->textpart === static::FILTER_EXCLUDE && $part->isTextPart())
            || ($this->textpart === static::FILTER_INCLUDE && !$part->isTextPart())) {
            return false;
        } elseif ($part->getParent() !== null
            && strcasecmp($part->getParent()->getHeaderValue('Content-Type'), 'multipart/signed') === 0
            && strcasecmp($part->getHeaderValue('Content-Type'), $part->getParent()->getHeaderParameter('Content-Type', 'protocol')) === 0) {
            if ($this->signedpart === static::FILTER_EXCLUDE) {
                return false;
            }
        } elseif ($this->signedpart === static::FILTER_INCLUDE) {
            return false;
        }
        foreach ($this->headers as $type => $values) {
            foreach ($values as $name => $header) {
                if (($type === static::FILTER_EXCLUDE && strcasecmp($part->getHeaderValue($name), $header) === 0)
                    || ($type === static::FILTER_INCLUDE && strcasecmp($part->getHeaderValue($name), $header) !== 0)) {
                    return false;
                }
            }
        }
        return true;
    }
}
