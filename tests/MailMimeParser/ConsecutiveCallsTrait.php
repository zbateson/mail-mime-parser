<?php

namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Provides a replacement for PHPUnit 9's withConsecutive() which was removed
 * in PHPUnit 10.
 *
 * Usage:
 *   ->with($this->consecutive(['arg1a', 'arg1b'], ['arg2a', 'arg2b']))
 * instead of:
 *   ->withConsecutive(['arg1a', 'arg1b'], ['arg2a', 'arg2b'])
 */
trait ConsecutiveCallsTrait
{
    /**
     * @param array<mixed> ...$parameterGroups Each group is an array of expected arguments for one call
     * @return Callback<mixed> ...
     */
    public function consecutive(array ...$parameterGroups) : array
    {
        $callIndex = 0;
        $maxArgs = max(array_map('count', $parameterGroups));

        $callbacks = [];
        for ($argIndex = 0; $argIndex < $maxArgs; $argIndex++) {
            $ai = $argIndex;
            $callbacks[] = new Callback(function (mixed $actual) use (&$callIndex, $ai, $parameterGroups, $maxArgs) : bool {
                // Only increment on the last argument position
                $currentCall = (int) floor($callIndex / $maxArgs);
                if ($currentCall >= count($parameterGroups)) {
                    $this->fail("Unexpected call #{$currentCall}, only " . count($parameterGroups) . ' calls expected.');
                }
                $group = $parameterGroups[$currentCall];
                $callIndex++;
                if ($ai >= count($group)) {
                    return true;
                }
                $expected = $group[$ai];
                if ($expected instanceof Constraint) {
                    $expected->evaluate($actual);
                    return true;
                }
                $this->assertEquals($expected, $actual);
                return true;
            });
        }
        return $callbacks;
    }
}
