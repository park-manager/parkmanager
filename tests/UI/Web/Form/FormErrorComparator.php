<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form;

use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use Symfony\Component\Form\FormError;

final class FormErrorComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        if (! \is_object($expected) || ! \is_object($actual)) {
            return false;
        }

        return $expected instanceof FormError && $actual instanceof FormError;
    }

    /**
     * @param FormError $expected
     * @param FormError $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        // Ignore the cause as this is to difficult to reproduce
        if ($expected->getMessage() === $actual->getMessage() &&
            $expected->getMessageTemplate() === $actual->getMessageTemplate() &&
            $expected->getMessageParameters() === $actual->getMessageParameters() &&
            $expected->getMessagePluralization() === $actual->getMessagePluralization()
        ) {
            return;
        }

        $expectedOrigin = $expected->getOrigin();

        if ($expectedOrigin !== null && $expectedOrigin === $actual->getOrigin()) {
            return;
        }

        throw new ComparisonFailure(
            $expected,
            $actual,
            $exportedExpected = $this->exporter->export($expected),
            $exportedActual = $this->exporter->export($actual),
            false,
            \sprintf(
                'Failed asserting that %s matches expected %s.',
                $exportedActual,
                $exportedExpected
            )
        );
    }
}
