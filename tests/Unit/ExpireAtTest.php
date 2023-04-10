<?php
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;
use DTApi\Helpers\TeHelper;
class ExpireAtTest extends TestCase
{
    public function testWillExpireAtWithin90Hours()
    {
        $due_time = Carbon::now()->addHours(50);
        $created_at = Carbon::now();

        $result = TeHelper::willExpireAt($due_time, $created_at);

        $this->assertEquals($result, $due_time->format('Y-m-d H:i:s'));
    }

    public function testWillExpireAtWithin24Hours()
    {
        $due_time = Carbon::now()->addHours(10);
        $created_at = Carbon::now();

        $expected_result = $created_at->addMinutes(90)->format('Y-m-d H:i:s');

        $result = TeHelper::willExpireAt($due_time, $created_at);

        $this->assertEquals($result, $expected_result);
    }

    public function testWillExpireAtWithin72Hours()
    {
        $due_time = Carbon::now()->addHours(60);
        $created_at = Carbon::now()->subHours(48);

        $expected_result = $created_at->addHours(16)->format('Y-m-d H:i:s');

        $result = TeHelper::willExpireAt($due_time, $created_at);

        $this->assertEquals($result, $expected_result);
    }

    public function testWillExpireAtAfter72Hours()
    {
        $due_time = Carbon::now()->addHours(100);
        $created_at = Carbon::now()->subHours(96);

        $expected_result = $due_time->subHours(48)->format('Y-m-d H:i:s');

        $result = TeHelper::willExpireAt($due_time, $created_at);

        $this->assertEquals($result, $expected_result);
    }
}