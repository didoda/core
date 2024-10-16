<?php
namespace BEdita\Core\Test\Fixture;

use BEdita\Core\TestSuite\Fixture\TestFixture;

/**
 * ObjectsFixture
 */
class ObjectsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        // 1
        [
            'object_type_id' => 4,
            'status' => 'on',
            'uname' => 'first-user',
            'locked' => 1,
            'deleted' => 0,
            'created' => '2016-05-13 07:09:23',
            'modified' => '2016-05-13 07:09:23',
            'published' => null,
            'title' => 'Mr. First User',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 2
        [
            'object_type_id' => 2,
            'status' => 'on',
            'uname' => 'title-one',
            'locked' => 1,
            'deleted' => 0,
            'created' => '2016-05-13 07:09:23',
            'modified' => '2016-05-13 07:09:23',
            'published' => '2016-05-13 07:09:23',
            'title' => 'title one',
            'description' => 'description here',
            'body' => 'body here',
            'extra' => '{"abstract": "abstract here", "list": ["one", "two", "three"]}',
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => '2016-05-13 07:09:23',
            'publish_end' => '2016-05-13 07:09:23',
        ],
        // 3
        [
            'object_type_id' => 2,
            'status' => 'draft',
            'uname' => 'title-two',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2016-05-12 07:09:23',
            'modified' => '2016-05-13 08:30:00',
            'published' => null,
            'title' => 'title two',
            'description' => 'description here',
            'body' => 'body here',
            'extra' => null,
            'lang' => null,
            'created_by' => 1,
            'modified_by' => 5,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 4
        [
            'object_type_id' => 3,
            'status' => 'on',
            'uname' => 'gustavo-supporto',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2016-05-13 07:09:23',
            'modified' => '2016-05-13 07:09:23',
            'published' => null,
            'title' => 'Gustavo Supporto profile',
            'description' => 'Some description about Gustavo',
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
        ],
        // 5
        [
            'object_type_id' => 4,
            'status' => 'on',
            'uname' => 'second-user',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2016-05-13 07:09:23',
            'modified' => '2016-05-13 07:09:23',
            'published' => null,
            'title' => 'Miss Second User',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 5,
            'modified_by' => 5,
            'custom_props' => '{"another_username":"synapse","another_email":"synapse@example.org"}',
        ],
        // 6
        [
            'object_type_id' => 2,
            'status' => 'on',
            'uname' => 'title-one-deleted',
            'locked' => 0,
            'deleted' => 1,
            'created' => '2016-10-13 07:09:23',
            'modified' => '2016-10-13 07:09:23',
            'published' => '2016-10-13 07:09:23',
            'title' => 'title one deleted',
            'description' => 'description removed',
            'body' => 'body no more',
            'extra' => '{"abstract": "what?"}',
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => '2016-10-13 07:09:23',
            'publish_end' => '2016-10-13 07:09:23',
        ],
        // 7
        [
            'object_type_id' => 2,
            'status' => 'on',
            'uname' => 'title-two-deleted',
            'locked' => 0,
            'deleted' => 1,
            'created' => '2016-10-13 07:09:23',
            'modified' => '2016-10-13 07:09:23',
            'published' => '2016-10-13 07:09:23',
            'title' => 'title two deleted',
            'description' => 'description removed',
            'body' => 'body no more',
            'extra' => '{"abstract": "what?"}',
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => '2016-10-13 07:09:23',
            'publish_end' => '2016-10-13 07:09:23',
        ],
        // 8
        [
            'object_type_id' => 6,
            'status' => 'on',
            'uname' => 'the-two-towers',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2017-02-20 07:09:23',
            'modified' => '2017-02-20 07:09:23',
            'published' => '2017-02-20 07:09:23',
            'title' => 'The Two Towers',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 9
        [
            'object_type_id' => 7,
            'status' => 'on',
            'uname' => 'event-one',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2017-03-08 07:09:23',
            'modified' => '2016-03-08 08:30:00',
            'published' => null,
            'title' => 'first event',
            'description' => 'event description goes here',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 10
        [
            'object_type_id' => 9,
            'status' => 'on',
            'uname' => 'media-one',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2017-03-08 07:09:23',
            'modified' => '2017-03-08 08:30:00',
            'published' => null,
            'title' => 'first media',
            'description' => 'media description goes here',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
            'custom_props' => '{"media_property":true}',
        ],
        // 11
        [
            'object_type_id' => 10,
            'status' => 'on',
            'uname' => 'root-folder',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2018-01-31 07:09:23',
            'modified' => '2018-01-31 08:30:00',
            'published' => null,
            'title' => 'Root Folder',
            'description' => 'first root folder',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 12
        [
            'object_type_id' => 10,
            'status' => 'on',
            'uname' => 'sub-folder',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2018-01-31 07:09:23',
            'modified' => '2018-01-31 08:30:00',
            'published' => null,
            'title' => 'Sub Folder',
            'description' => 'sub folder of root folder',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 13
        [
            'object_type_id' => 10,
            'status' => 'on',
            'uname' => 'another-root-folder',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2018-03-08 12:20:00',
            'modified' => '2018-03-08 12:20:00',
            'published' => null,
            'title' => 'Another Root Folder',
            'description' => 'second root folder',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
        // 14
        [
            'object_type_id' => 9,
            'status' => 'on',
            'uname' => 'media-two',
            'locked' => 0,
            'deleted' => 0,
            'created' => '2018-03-22 16:42:31',
            'modified' => '2018-03-22 16:42:31',
            'published' => null,
            'title' => 'second media',
            'description' => 'another media description goes here',
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
            'custom_props' => '{"media_property":false}',
        ],
        // 15 (ghost object)
        [
            'object_type_id' => 2,
            'status' => 'draft',
            'uname' => '__deleted-15',
            'locked' => 1,
            'deleted' => 1,
            'created' => '2018-07-13 07:09:23',
            'modified' => '2018-07-15 08:30:00',
            'published' => null,
            'title' => null,
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => null,
            'created_by' => 1,
            'modified_by' => 1,
            'publish_start' => null,
            'publish_end' => null,
        ],
    ];

    /**
     * Before Build Schema callback
     *
     * Change `status` type to 'string' to avoid errors
     *
     * @return void
     */
    public function beforeBuildSchema()
    {
        $this->fields['status']['type'] = 'string';

        unset($this->fields['_constraints']['objects_modifiedby_fk']);
        unset($this->fields['_constraints']['objects_createdby_fk']);
    }
}
