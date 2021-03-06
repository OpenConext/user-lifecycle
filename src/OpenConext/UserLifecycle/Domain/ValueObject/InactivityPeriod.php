<?php

/**
 * Copyright 2018 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenConext\UserLifecycle\Domain\ValueObject;

use OpenConext\UserLifecycle\Domain\Exception\InvalidInactivityPeriodException;

class InactivityPeriod
{
    /**
     * @var int
     */
    private $period;

    public function __construct($inactivityPeriod)
    {
        if (!is_int($inactivityPeriod) || $inactivityPeriod <= 0) {
            throw new InvalidInactivityPeriodException('The inactivity period must be an integer value');
        }

        $this->period = $inactivityPeriod;
    }

    /**
     * @return int
     */
    public function getInactivityPeriod()
    {
        return $this->period;
    }
}
