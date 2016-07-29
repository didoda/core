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

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * {@see \BEdita\Core\Model\Table\RolesTable} Test Case
 *
 * @coversDefaultClass \BEdita\Core\Model\Table\RolesTable
 */
class RolesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \BEdita\Core\Model\Table\RolesTable
     */
    public $Roles;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.roles',
    ];

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->Roles = TableRegistry::get('Roles');
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->Roles);

        parent::tearDown();
    }

    /**
     * Test initialization.
     *
     * @return void
     * @covers ::initialize()
     */
    public function testInitialization()
    {
        $this->Roles->initialize([]);
        $this->assertEquals('roles', $this->Roles->table());
        $this->assertEquals('id', $this->Roles->primaryKey());
        $this->assertEquals('name', $this->Roles->displayField());

        $this->assertInstanceOf('\Cake\ORM\Association\BelongsToMany', $this->Roles->Users);
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
                    'name' => 'unique_role_name',
                ],
            ],
            'notUnique' => [
                false,
                [
                    'name' => 'first role',
                ],
            ],
        ];
    }

    /**
     * Test validation.
     *
     * @param bool $expected Expected result.
     * @param array $data Data to be validated.
     *
     * @return void
     * @dataProvider validationProvider
     * @covers ::validationDefault
     * @covers ::buildRules
     */
    public function testValidation($expected, array $data)
    {
        $role = $this->Roles->newEntity();
        $this->Roles->patchEntity($role, $data);

        $error = (bool)$role->errors();
        $this->assertEquals($expected, !$error);

        if ($expected) {
            $success = $this->Roles->save($role);
            $this->assertTrue((bool)$success);
        }
    }
}
