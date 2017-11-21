<?php

namespace App\Tests\Entity;

use App\Entity\JoindInComment;
use App\Entity\JoindInEvent;
use App\Entity\JoindInTalk;
use App\Entity\JoindInUser;
use App\Entity\Raffle;
use App\Exception\NoCommentsToRaffleException;
use App\Exception\NoEventsToRaffleException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Raffle
 * @group  todo
 */
class RaffleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @expectedException \App\Exception\NoEventsToRaffleException
     */
    public function testCannotCreateEmptyRaffle()
    {
        Raffle::create([]);
    }

    /**
     * @expectedException \App\Exception\NoCommentsToRaffleException
     */
    public function testCannotStartARaffleWithNoCommentsInEvents()
    {

        // Arrange.

        $event1 = new JoindInEvent(1, 'Meetup #1', new \DateTime());
        $talk1 = new JoindInTalk(1, 'Cool talk', $event1);

        $event1->addTalk($talk1);
        $events = new ArrayCollection([$event1]);

        // Act.

        new Raffle('id', $events);
    }

    public function testRaffleWillBeCreated()
    {

        // Arrange.

        $talk1 = Mockery::mock(JoindInTalk::class);
        $talk1->shouldReceive('getCommentCount')->andReturn(1);

        $event1 = new JoindInEvent(1, 'Meetup #1', new \DateTime());
        $event1->addTalk($talk1);

        $events = new ArrayCollection([$event1]);

        // Act.

        $raffle = new Raffle('id', $events);

        // Assert.

        $this->assertInstanceOf(Raffle::class, $raffle);
    }

    public function testRaffleWillBeCreated2()
    {

        // Arrange.

        $talk1 = Mockery::mock(JoindInTalk::class);
        $talk1->shouldReceive('getCommentCount')->andReturn(1);

        $event1 = Mockery::mock(JoindInEvent::class);
        $event1->shouldReceive('getTalks')->andReturn(new ArrayCollection([$talk1]));

        $events = new ArrayCollection([$event1]);

        // Act.

        $raffle = new Raffle('id', $events);

        // Assert.

        $this->assertInstanceOf(Raffle::class, $raffle);
    }

}
