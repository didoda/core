<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\TestCase\Model\Table;

use Cake\Cache\Cache;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * BEdita\Core\Model\Table\EndpointsTable Test Case
 *
 * @coversDefaultClass \BEdita\Core\Model\Table\EndpointsTable
 */
class EndpointsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \BEdita\Core\Model\Table\EndpointsTable
     */
    public $Endpoints;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.Endpoints',
        'plugin.BEdita/Core.Relations',
        'plugin.BEdita/Core.RelationTypes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Endpoints = TableRegistry::getTableLocator()->get('Endpoints');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Endpoints);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->Endpoints->initialize([]);
        $this->assertEquals('endpoints', $this->Endpoints->getTable());
        $this->assertEquals('id', $this->Endpoints->getPrimaryKey());
        $this->assertEquals('name', $this->Endpoints->getDisplayField());

        $this->assertInstanceOf('\Cake\ORM\Behavior\TimestampBehavior', $this->Endpoints->behaviors()->get('Timestamp'));
        $this->assertInstanceOf('\Cake\ORM\Association\hasMany', $this->Endpoints->EndpointPermissions);
        $this->assertInstanceOf('\BEdita\Core\Model\Table\EndpointPermissionsTable', $this->Endpoints->EndpointPermissions->getTarget());
    }

    /**
     * Data provider for `testValidation` test case.
     *
     * @return array
     */
    public function validationProvider()
    {
        return [
            'valid' => [
                true,
                [
                    'name' => 'custom_endpoint',
                ],
            ],
            'notUniqueName' => [
                false,
                [
                    'name' => 'home',
                ],
            ],
            'missingName' => [
                false,
                [
                    'description' => 'Where is apendpoint name?',
                ],
            ],
        ];
    }

    /**
     * Test validation.
     *
     * @param bool $expected Expected result.
     * @param array $data Data to be validated.
     * @return void
     * @dataProvider validationProvider
     * @coversNothing
     */
    public function testValidation($expected, array $data)
    {
        $endpoint = $this->Endpoints->newEntity($data);
        $error = (bool)$endpoint->getErrors();
        $this->assertEquals($expected, !$error);
        if ($expected) {
            $success = $this->Endpoints->save($endpoint);
            $this->assertTrue((bool)$success);
        }
    }

    /**
     * Data provider for `testBuildRules` test case.
     *
     * @return array
     */
    public function buildRulesProvider()
    {
        return [
            'wrongObjectType' => [
                false,
                [
                    'name' => 'custom_endpoint',
                    'object_type_id' => 1234,
                ],
            ],
            'notUnique' => [
                false,
                [
                    'name' => 'home',
                ],
            ],
        ];
    }

    /**
     * Test build rules validation.
     *
     * @param bool $expected Expected result.
     * @param array $data Data to be validated.
     * @return void
     * @dataProvider buildRulesProvider
     * @coversNothing
     */
    public function testBuildRules($expected, array $data)
    {
        $endpoint = $this->Endpoints->newEntity($data, ['validate' => false]);
        $success = $this->Endpoints->save($endpoint);
        $this->assertEquals($expected, (bool)$success, print_r($endpoint->getErrors(), true));
    }

    /**
     * Data provider for `testFetchId` test case.
     *
     * @return array
     */
    public function fetchIdProvider()
    {
        return [
            '/auth' => [
                1,
                '/auth',
            ],
            '/home/sweet/home' => [
                2,
                '/home/sweet/home',
            ],
            '/' => [
                null,
                '/',
            ],
            '/this/endpoint/definitely/doesnt/exist' => [
                null,
                '/this/endpoint/definitely/doesnt/exist',
            ],
            '/disabled/endpoint' => [
                new NotFoundException('Resource not found.'),
                '/disabled/endpoint',
            ],
        ];
    }

    /**
     * Test getting endpoint from request.
     *
     * @param mixed $expected Expected endpoint ID, null, or exception.
     * @param string $path Request path.
     * @return void
     * @dataProvider fetchIdProvider()
     * @covers ::fetchId()
     */
    public function testFetchId($expected, string $path): void
    {
        $cacheConf = $this->Endpoints->behaviors()->get('QueryCache')->getConfig('cacheConfig');
        Cache::clear(false, $cacheConf);
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());
        }

        $result = $this->Endpoints->fetchId($path);
        static::assertEquals($expected, $result);
    }
}
