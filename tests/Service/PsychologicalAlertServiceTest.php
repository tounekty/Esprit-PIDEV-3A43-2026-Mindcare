<?php

namespace App\Tests\Service;

use App\Entity\Mood;
use App\Entity\PsychologicalAlert;
use App\Entity\User;
use App\Repository\CommentaireRepository;
use App\Repository\MoodRepository;
use App\Repository\PsychologicalAlertRepository;
use App\Service\NotificationService;
use App\Service\PsychologicalAlertService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PsychologicalAlertServiceTest extends TestCase
{
    public function testPositiveMomentumMessageWhenThreeHappyMoods(): void
    {
        $moodRepository = $this->createMock(MoodRepository::class);
        $commentRepository = $this->createMock(CommentaireRepository::class);
        $alertRepository = $this->createMock(PsychologicalAlertRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $moodRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($this->buildQueryBuilderMock([
                $this->createMood('heureux'),
                $this->createMood('heureux'),
                $this->createMood('heureux'),
            ]));

        $service = new PsychologicalAlertService(
            $moodRepository,
            $commentRepository,
            $alertRepository,
            $entityManager,
            $notificationService
        );

        $message = $service->getPositiveMomentumMessage($this->createUser());

        $this->assertSame('Excellent travail ! 3 moods heureux consecutifs. Continuez comme ca !', $message);
    }

    public function testPositiveMomentumMessageReturnsNullWhenMoodIsMixed(): void
    {
        $moodRepository = $this->createMock(MoodRepository::class);
        $commentRepository = $this->createMock(CommentaireRepository::class);
        $alertRepository = $this->createMock(PsychologicalAlertRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $moodRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($this->buildQueryBuilderMock([
                $this->createMood('heureux'),
                $this->createMood('triste'),
                $this->createMood('heureux'),
            ]));

        $service = new PsychologicalAlertService(
            $moodRepository,
            $commentRepository,
            $alertRepository,
            $entityManager,
            $notificationService
        );

        $message = $service->getPositiveMomentumMessage($this->createUser());

        $this->assertNull($message);
    }

    public function testCheckUserAlertsCreatesAlertForThreeConsecutiveNegativeMoods(): void
    {
        $moodRepository = $this->createMock(MoodRepository::class);
        $commentRepository = $this->createMock(CommentaireRepository::class);
        $alertRepository = $this->createMock(PsychologicalAlertRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $moodRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($this->buildQueryBuilderMock([
                $this->createMood('triste'),
                $this->createMood('stresse'),
                $this->createMood('fatigue'),
            ]));

        $alertRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->buildQueryBuilderMockForSingleResult(null));

        $commentRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($this->buildQueryBuilderMock([]));

        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->exactly(2))->method('flush');

        $notificationService
            ->expects($this->exactly(2))
            ->method('notifyAlert')
            ->withConsecutive(
                [$this->equalTo('admin'), $this->equalTo('Alerte Psychologique'), $this->isType('string'), $this->isInstanceOf(PsychologicalAlert::class)],
                [$this->equalTo('psychologue'), $this->equalTo('Alerte Psychologique'), $this->isType('string'), $this->isInstanceOf(PsychologicalAlert::class)]
            );

        $service = new PsychologicalAlertService(
            $moodRepository,
            $commentRepository,
            $alertRepository,
            $entityManager,
            $notificationService
        );

        $service->checkUserAlerts($this->createUser());
    }

    public function testCheckUserAlertsDoesNotCreateAlertWhenNotConsecutiveNegative(): void
    {
        $moodRepository = $this->createMock(MoodRepository::class);
        $commentRepository = $this->createMock(CommentaireRepository::class);
        $alertRepository = $this->createMock(PsychologicalAlertRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $notificationService = $this->createMock(NotificationService::class);

        $moodRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($this->buildQueryBuilderMock([
                $this->createMood('triste'),
                $this->createMood('heureux'),
                $this->createMood('fatigue'),
            ]));

        $alertRepository->expects($this->never())->method('createQueryBuilder');

        $commentRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($this->buildQueryBuilderMock([]));

        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');
        $notificationService->expects($this->never())->method('notifyAlert');

        $service = new PsychologicalAlertService(
            $moodRepository,
            $commentRepository,
            $alertRepository,
            $entityManager,
            $notificationService
        );

        $service->checkUserAlerts($this->createUser());
    }

    private function createMood(string $humeur): Mood
    {
        $mood = new Mood();
        $mood->setHumeur($humeur);

        return $mood;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('Student');
        $user->setEmail('student@example.com');
        $user->setPassword('secret');

        return $user;
    }

    /**
     * @param array<int, mixed> $results
     */
    private function buildQueryBuilderMock(array $results): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($results);

        /** @var QueryBuilder&MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    private function buildQueryBuilderMockForSingleResult(mixed $result): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn($result);

        /** @var QueryBuilder&MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }
}
