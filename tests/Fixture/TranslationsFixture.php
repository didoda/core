<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2018 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\Fixture;

use BEdita\Core\TestSuite\Fixture\TestFixture;

/**
 * Fixture for `translations` table.
 */
class TranslationsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'object_id' => 2,
                'lang' => 'it-IT',
                'status' => 'on',
                'created' => '2018-01-01 00:00:00',
                'modified' => '2018-01-01 00:00:00',
                'created_by' => 1,
                'modified_by' => 1,
                'translated_fields' => json_encode([
                    'title' => 'titolo uno',
                    'description' => 'descrizione qui',
                    'body' => 'contenuto qui',
                    'extra' => [
                        'abstract' => 'estratto qui',
                        'list' => ['uno', 'due', 'tre'],
                    ],
                ]),
            ],
            [
                'object_id' => 2,
                'lang' => 'fr',
                'status' => 'on',
                'created' => '2018-01-01 00:00:00',
                'modified' => '2018-01-01 00:00:00',
                'created_by' => 1,
                'modified_by' => 1,
                'translated_fields' => json_encode([
                    'description' => 'description ici',
                    'extra' => [
                        'list' => ['on', 'deux', 'trois'],
                    ],
                ]),
            ],
            [
                'object_id' => 2,
                'lang' => 'es',
                'status' => 'draft',
                'created' => '2018-01-01 00:00:00',
                'modified' => '2018-01-01 00:00:00',
                'created_by' => 1,
                'modified_by' => 1,
                'translated_fields' => json_encode([
                    'description' => 'descripción aquí',
                    'extra' => [
                        'list' => ['uno', 'dos', 'tres'],
                    ],
                ]),
            ],
        ];

        parent::init();
    }
}
