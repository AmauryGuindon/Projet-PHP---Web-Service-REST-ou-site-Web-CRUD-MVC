<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanMongoCollections();
    }

    private function cleanMongoCollections(): void
    {
        try {
            $db = DB::connection('mongodb')->getMongoDB();
            foreach ($db->listCollectionNames() as $name) {
                $db->selectCollection($name)->drop();
            }
        } catch (\Exception $e) {
            // Ignore if MongoDB not available
        }
    }
}
