<?php
namespace ZBateson\MailMimeParser\Stream;

/**
 * Unfortunately using a class object specifically to handle leftovers seems to
 * be the only way.  The last call to php_user_filter with $closing set to true
 * doesn't seem to be 'writable' to $out, if the entire stream is in a single
 * buffer.  If, however, the stream is large enough to be buffered multiple
 * times, the before last call seems to both contain data and have $closing set
 * to true.
 *
 * @author Zaahid Bateson <zbateson@mail.ubc.ca>
 */
class StreamLeftover
{
    public $value = '';
    public $encodedValue = '';
}
