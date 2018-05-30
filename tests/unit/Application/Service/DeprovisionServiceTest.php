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

namespace OpenConext\UserLifecycle\Tests\Unit\Application\Service;

use InvalidArgumentException;
use Mockery as m;
use Mockery\Mock;
use OpenConext\UserLifecycle\Application\Service\DeprovisionService;
use OpenConext\UserLifecycle\Domain\Client\InformationResponseCollectionInterface;
use OpenConext\UserLifecycle\Domain\Collection\LastLoginCollectionInterface;
use OpenConext\UserLifecycle\Domain\Entity\LastLogin;
use OpenConext\UserLifecycle\Domain\Service\LastLoginServiceInterface;
use OpenConext\UserLifecycle\Domain\Service\SanityCheckServiceInterface;
use OpenConext\UserLifecycle\Infrastructure\UserLifecycleBundle\Client\DeprovisionClientCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeprovisionServiceTest extends TestCase
{
    /**
     * @var DeprovisionService
     */
    private $service;

    /**
     * @var DeprovisionClientCollection|Mock
     */
    private $apiCollection;

    /**
     * @var SanityCheckServiceInterface|Mock
     */
    private $sanityChecker;

    /**
     * @var LastLoginServiceInterface|Mock
     */
    private $lastLoginService;

    public function setUp()
    {
        $this->apiCollection = m::mock(DeprovisionClientCollection::class);
        $this->sanityChecker = m::mock(SanityCheckServiceInterface::class);
        $this->lastLoginService = m::mock(LastLoginServiceInterface::class);
        $logger = m::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->service = new DeprovisionService(
            $this->apiCollection,
            $this->sanityChecker,
            $this->lastLoginService,
            $logger
        );
    }

    public function test_deprovision()
    {
        // Setup the test using test doubles
        $personId = 'jay-leno';
        $collection = m::mock(InformationResponseCollectionInterface::class);
        $collection
            ->shouldReceive('jsonSerialize')
            ->andReturn('{"only": "test"}');

        $this->apiCollection
            ->shouldReceive('deprovision')
            ->andReturnUsing(
                function ($expectedCollabPersonId, $expectedDryRunState) use ($collection) {
                    $this->assertEquals('jay-leno', $expectedCollabPersonId->getCollabPersonId());
                    $this->assertFalse($expectedDryRunState);

                    return $collection;
                }
            );

        // Call the readInformationFor method
        $response = $this->service->deprovision($personId);

        $this->assertJson($response);
    }

    public function test_deprovision_dry_run()
    {
        // Setup the test using test doubles
        $personId = 'jeff-beck';
        $collection = m::mock(InformationResponseCollectionInterface::class);
        $collection
            ->shouldReceive('jsonSerialize')
            ->andReturn('{"only": "test"}');

        $this->apiCollection
            ->shouldReceive('deprovision')
            ->andReturnUsing(function ($expectedCollabPersonId, $expectedDryRunState) use ($collection) {
                $this->assertEquals('jeff-beck', $expectedCollabPersonId->getCollabPersonId());
                $this->assertTrue($expectedDryRunState);
                return $collection;
            });

        // Call the readInformationFor method
        $response = $this->service->deprovision($personId, true);

        $this->assertJson($response);
    }

    public function test_deprovision_empty_person_id()
    {
        // Setup the test using test doubles
        $personId = '';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please pass a non empty collabPersonId');

        $this->service->deprovision($personId);
    }

    public function test_batch_deprovision()
    {
        $mockCollection = m::mock(LastLoginCollectionInterface::class);
        $mockUser1 = $this->buildMockLastLoginEntry('jack-black');
        $mockUser2 = $this->buildMockLastLoginEntry('jan-berry');

        $this->lastLoginService
            ->shouldReceive('findUsersForDeprovision')
            ->andReturn($mockCollection);

        $this->sanityChecker
            ->shouldReceive('check')
            ->with($mockCollection)
            ->andReturnNull();

        $mockCollection
            ->shouldReceive('getData')
            ->andReturn([$mockUser1, $mockUser2]);

        $collection = m::mock(InformationResponseCollectionInterface::class);
        $collection
            ->shouldReceive('jsonSerialize')
            ->andReturn('{"only": "test"}');

        $this->apiCollection
            ->shouldReceive('deprovision')
            ->andReturnUsing(
                function ($expectedCollabPersonId, $expectedDryRunState) use ($mockCollection, $collection) {
                    $this->assertContains($expectedCollabPersonId->getCollabPersonId(), ['jack-black', 'jan-berry']);
                    $this->assertFalse($expectedDryRunState);

                    return $collection;
                }
            );

        $this->service->batchDeprovision();

    }

    public function test_batch_deprovision_dry_run()
    {
        $mockCollection = m::mock(LastLoginCollectionInterface::class);
        $mockUser1 = $this->buildMockLastLoginEntry('jack-black');
        $mockUser2 = $this->buildMockLastLoginEntry('jan-berry');

        $this->lastLoginService
            ->shouldReceive('findUsersForDeprovision')
            ->andReturn($mockCollection);

        $this->sanityChecker
            ->shouldReceive('check')
            ->with($mockCollection)
            ->andReturnNull();

        $mockCollection
            ->shouldReceive('getData')
            ->andReturn([$mockUser1, $mockUser2]);

        $collection = m::mock(InformationResponseCollectionInterface::class);
        $collection
            ->shouldReceive('jsonSerialize')
            ->andReturn('{"only": "test"}');

        $this->apiCollection
            ->shouldReceive('deprovision')
            ->andReturnUsing(
                function ($expectedCollabPersonId, $expectedDryRunState) use ($mockCollection, $collection) {
                    $this->assertContains($expectedCollabPersonId->getCollabPersonId(), ['jack-black', 'jan-berry']);
                    $this->assertTrue($expectedDryRunState);

                    return $collection;
                }
            );

        $this->service->batchDeprovision(true);

    }

    private function buildMockLastLoginEntry($personId)
    {
        $lastLogin = m::mock(LastLogin::class);
        $lastLogin
            ->shouldReceive('getCollabPersonId')
            ->andReturn($personId);

        return $lastLogin;
    }

}