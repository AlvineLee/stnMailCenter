<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\ExampleSeeder;
use Tests\Support\Models\ExampleModel;

/**
 * @internal
 * 
 * 주의: SQLite3를 사용하지 않으므로 이 테스트는 스킵됩니다.
 * DatabaseTestTrait를 사용하지 않도록 수정하여 데이터베이스 연결을 시도하지 않습니다.
 */
final class ExampleDatabaseTest extends CIUnitTestCase
{
    // DatabaseTestTrait를 사용하지 않음 (SQLite3 연결 시도 방지)
    // use DatabaseTestTrait;

    // protected $seed = ExampleSeeder::class;
    
    /**
     * SQLite3를 사용하지 않으므로 모든 테스트 스킵
     */
    public function testModelFindAll(): void
    {
        $this->markTestSkipped('SQLite3를 사용하지 않으므로 데이터베이스 테스트를 스킵합니다.');
    }

    public function testModelFindAll(): void
    {
        $model = new ExampleModel();

        // Get every row created by ExampleSeeder
        $objects = $model->findAll();

        // Make sure the count is as expected
        $this->assertCount(3, $objects);
    }

    public function testSoftDeleteLeavesRow(): void
    {
        $this->markTestSkipped('SQLite3를 사용하지 않으므로 데이터베이스 테스트를 스킵합니다.');
    }
}
