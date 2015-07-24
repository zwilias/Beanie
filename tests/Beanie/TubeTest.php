<?php


namespace Beanie;


use Beanie\Command\PeekReadyCommand;
use Beanie\Command\Response;
use Beanie\Exception\NotFoundException;
use Beanie\Exception\OutOfMemoryException;
use Beanie\Job\Job;
use Beanie\Tube\TubeStatus;
use Beanie\Tube\Tube;

class TubeTest extends \PHPUnit_Framework_TestCase
{
    const TEST_TUBE = 'test';

    public function testConstruct_propagatesTubeNameToTubeStatus()
    {
        $tube = new Tube(self::TEST_TUBE, $this->getServerMock());


        $this->assertEquals(self::TEST_TUBE, $tube->getTubeStatus()->getCurrentTube());
    }

    public function testTransformTubeStatusTo_appliesTubeNameOnly()
    {
        $tubeStatus = new TubeStatus();
        $tubeStatus->setCurrentTube(self::TEST_TUBE);
        $tubeStatus->addWatchedTube(self::TEST_TUBE);


        $tube = new Tube(Beanie::DEFAULT_TUBE, $this->getServerMock());
        $tube->transformTubeStatusTo($tubeStatus);


        $this->assertEquals(self::TEST_TUBE, $tube->getTubeStatus()->getCurrentTube());
        $this->assertEquals([Beanie::DEFAULT_TUBE], $tube->getTubeStatus()->getWatchedTubes());
        $this->assertNotSame($tubeStatus, $tube->getTubeStatus());
    }

    public function testTransformTubeStatusTo_respectsMode()
    {
        $tubeStatus = new TubeStatus();
        $tubeStatus->setCurrentTube(self::TEST_TUBE);


        $tube = new Tube(Beanie::DEFAULT_TUBE, $this->getServerMock());
        $tube->transformTubeStatusTo($tubeStatus, TubeStatus::TRANSFORM_WATCHED);


        $this->assertEquals(Beanie::DEFAULT_TUBE, $tube->getTubeStatus()->getCurrentTube());
        $this->assertEquals([Beanie::DEFAULT_TUBE], $tube->getTubeStatus()->getWatchedTubes());
        $this->assertNotSame($tubeStatus, $tube->getTubeStatus());
    }

    public function testPeekReady_returnsJob()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_OK, ['id' => 123, 'data' => 'test'], $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PeekReadyCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $job = $tube->peekReady();


        $this->assertInstanceOf('\Beanie\Job\Job', $job);
        $this->assertEquals(123, $job->getId());
        $this->assertEquals('test', $job->getData());
        $this->assertEquals(Job::STATE_UNKNOWN, $job->getState());
    }

    public function testPeekReady_ServerThrowsNotFound_returnsNull()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand', '__toString']);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PeekReadyCommand'))
            ->willThrowException(new NotFoundException(new PeekReadyCommand(), $serverMock));

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $job = $tube->peekReady();


        $this->assertNull($job);
    }

    /**
     * @expectedException \Beanie\Exception\OutOfMemoryException
     */
    public function testPeekReady_ServerThrowsOutOfMemory_propagated()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand', '__toString']);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PeekReadyCommand'))
            ->willThrowException(new OutOfMemoryException(new PeekReadyCommand(), $serverMock));

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $tube->peekReady();
    }

    public function testPeekBuried_returnsJob()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_OK, ['id' => 123, 'data' => 'test'], $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PeekBuriedCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $job = $tube->peekBuried();


        $this->assertInstanceOf('\Beanie\Job\Job', $job);
        $this->assertEquals(123, $job->getId());
        $this->assertEquals('test', $job->getData());
        $this->assertEquals(Job::STATE_UNKNOWN, $job->getState());
    }

    public function testPeekDelayed_returnsJob()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_OK, ['id' => 123, 'data' => 'test'], $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PeekDelayedCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $job = $tube->peekDelayed();


        $this->assertInstanceOf('\Beanie\Job\Job', $job);
        $this->assertEquals(123, $job->getId());
        $this->assertEquals('test', $job->getData());
        $this->assertEquals(Job::STATE_UNKNOWN, $job->getState());
    }

    public function testKick_returnsNumberOfKickedJobs()
    {
        $kickedJobsExpected = 10;

        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_KICKED, $kickedJobsExpected, $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\KickCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $kickedJobs = $tube->kick(50);


        $this->assertEquals($kickedJobsExpected, $kickedJobs);
    }

    /**
     * @expectedException \Beanie\Exception\InvalidArgumentException
     */
    public function testKick_negativeJobs_throwsException()
    {
        $tube = new Tube(Beanie::DEFAULT_TUBE, $this->getServerMock());


        $tube->kick(-1);
    }

    /**
     * @expectedException \Beanie\Exception\InvalidArgumentException
     */
    public function testKick_zeroJobs_throwsException()
    {
        $tube = new Tube(Beanie::DEFAULT_TUBE, $this->getServerMock());


        $tube->kick(0);
    }

    public function testStats_returnsStats()
    {
        $expectedStats = ['alive' => true, 'jobs' => 0];
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_OK, $expectedStats, $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\StatsTubeCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $stats = $tube->stats();


        $this->assertEquals($expectedStats, $stats);
    }

    public function testPause_returnsBoolean()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_PAUSED, null, $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PauseTubeCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $paused = $tube->pause(10);


        $this->assertTrue($paused);
    }

    public function testPause_zeroIsAccepted()
    {
        $serverMock = $this->getServerMockCheckTransform(['dispatchCommand']);
        $response = new Response(Response::RESPONSE_PAUSED, null, $serverMock);

        $serverMock
            ->expects($this->once())
            ->method('dispatchCommand')
            ->with($this->isInstanceOf('\Beanie\Command\PauseTubeCommand'))
            ->willReturn($response);

        $tube = new Tube(Beanie::DEFAULT_TUBE, $serverMock);


        $paused = $tube->pause(0);


        $this->assertTrue($paused);
    }

    /**
     * @expectedException \Beanie\Exception\InvalidArgumentException
     */
    public function testPause_negative_throwsException()
    {
        $tube = new Tube(Beanie::DEFAULT_TUBE, $this->getServerMock());


        $tube->pause(-1);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|\Beanie\Server\Server
     */
    protected function getServerMock($methods = [])
    {
        return $this
            ->getMockBuilder('\Beanie\Server\Server')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $extraMethods
     * @return Server\Server|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getServerMockCheckTransform($extraMethods = [])
    {
        $serverMock = $this->getServerMock(array_merge($extraMethods, ['transformTubeStatusTo']));

        $serverMock
            ->expects($this->once())
            ->method('transformTubeStatusTo')
            ->with($this->anything(), TubeStatus::TRANSFORM_USE)
            ->willReturn(true);

        return $serverMock;
    }
}
