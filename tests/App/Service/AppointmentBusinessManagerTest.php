<?php

namespace App\Tests\App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentBusinessManager;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class AppointmentBusinessManagerTest extends TestCase
{
    private AppointmentBusinessManager $manager;
    private AppointmentRepository $appointmentRepositoryMock;
    private User $studentUser;
    private User $psychologistUser;

    protected function setUp(): void
    {
        // Mock the repository
        $this->appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);

        // Create the manager with mocked repository
        $this->manager = new AppointmentBusinessManager($this->appointmentRepositoryMock);

        // Create test users
        $this->studentUser = new User();
        $this->studentUser->setFirstName('John');
        $this->studentUser->setLastName('Student');
        $this->studentUser->setEmail('student@example.com');

        $this->psychologistUser = new User();
        $this->psychologistUser->setFirstName('Dr.');
        $this->psychologistUser->setLastName('Psychologist');
        $this->psychologistUser->setEmail('psy@example.com');
    }

    // ============================================================================
    // RULE #1: Weekly Booking Limit Tests
    // ============================================================================

    public function testCanStudentBookWhenNoAppointmentThisWeek(): void
    {
        // Mock: no appointment this week
        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(false);

        $appointmentDate = new DateTime('+3 days');
        $result = $this->manager->canStudentBookThisWeek(
            $this->studentUser,
            $this->psychologistUser,
            $appointmentDate
        );

        $this->assertTrue($result, 'Student should be able to book when no appointment exists this week');
    }

    public function testCannotStudentBookTwicePerWeek(): void
    {
        // Mock: appointment already exists this week
        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(true);

        $appointmentDate = new DateTime('+3 days');
        $result = $this->manager->canStudentBookThisWeek(
            $this->studentUser,
            $this->psychologistUser,
            $appointmentDate
        );

        $this->assertFalse($result, 'Student should NOT be able to book twice per week');
    }

    public function testRefusedAppointmentDoesNotCountTowardLimit(): void
    {
        // Mock: hasAppointmentThisWeekWithPsychologue should return false
        // because refused appointments are excluded in the repository method
        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(false);

        $appointmentDate = new DateTime('+3 days');
        $result = $this->manager->canStudentBookThisWeek(
            $this->studentUser,
            $this->psychologistUser,
            $appointmentDate
        );

        $this->assertTrue($result, 'Refused appointments should not count toward weekly limit');
    }

    // ============================================================================
    // RULE #2: Fixed Duration (1 Hour) Tests
    // ============================================================================

    public function testGetAppointmentEndTimeIsOneHourLater(): void
    {
        $startTime = new DateTime('2026-03-03 14:00:00');
        $endTime = $this->manager->getAppointmentEndTime($startTime);

        // Calculate expected end time
        $expected = (clone $startTime)->modify('+60 minutes');

        $this->assertEquals($expected, $endTime, 'End time should be 1 hour after start time');
    }

    public function testAppointmentDurationIs60Minutes(): void
    {
        $duration = $this->manager->getAppointmentDurationMinutes();
        $this->assertEquals(60, $duration, 'Appointment duration should be 60 minutes');
    }

    public function testEndTimeCalculationWithDifferentTimezones(): void
    {
        $startTime = new DateTime('2026-03-03 14:00:00', new DateTimeZone('Europe/Paris'));
        $endTime = $this->manager->getAppointmentEndTime($startTime);

        $this->assertEquals(15, $endTime->format('H'), 'Hour should be 15 (15:00)');
        $this->assertEquals(0, $endTime->format('i'), 'Minutes should be 0');
    }

    // ============================================================================
    // RULE #3: Time Slot Conflict Prevention Tests
    // ============================================================================

    public function testNoConflictWhenNoExistingAppointments(): void
    {
        // Mock: no existing appointments
        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([]);

        $requestedDate = new DateTime('2026-03-03 14:00:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate);

        $this->assertFalse($result, 'No conflict when psychologist has no appointments');
    }

    public function testConflictDetectionWithOverlappingAppointment(): void
    {
        // Create an existing appointment
        $existingAppointment = new Appointment();
        $existingAppointment->setDate(new DateTime('2026-03-03 14:00:00'));
        $existingAppointment->setStatus('accepted');
        $existingAppointment->setPsychologue($this->psychologistUser);

        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$existingAppointment]);

        // Try to book at same time
        $requestedDate = new DateTime('2026-03-03 14:00:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate);

        $this->assertTrue($result, 'Should detect conflict when trying to book same time');
    }

    public function testConflictDetectionDuringExistingAppointment(): void
    {
        // Create appointment from 14:00 to 15:00
        $existingAppointment = new Appointment();
        $existingAppointment->setDate(new DateTime('2026-03-03 14:00:00'));
        $existingAppointment->setStatus('accepted');
        $existingAppointment->setPsychologue($this->psychologistUser);

        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$existingAppointment]);

        // Try to book at 14:30 (during existing appointment)
        $requestedDate = new DateTime('2026-03-03 14:30:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate);

        $this->assertTrue($result, 'Should detect conflict when booking during existing appointment');
    }

    public function testNoConflictAfterExistingAppointment(): void
    {
        // Create appointment from 14:00 to 15:00
        $existingAppointment = new Appointment();
        $existingAppointment->setDate(new DateTime('2026-03-03 14:00:00'));
        $existingAppointment->setStatus('accepted');
        $existingAppointment->setPsychologue($this->psychologistUser);

        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$existingAppointment]);

        // Try to book at 15:00 (right after existing appointment)
        $requestedDate = new DateTime('2026-03-03 15:00:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate);

        $this->assertFalse($result, 'No conflict when booking after existing appointment ends');
    }

    public function testRefusedAppointmentDoesNotCreateConflict(): void
    {
        // Create refused appointment
        $refusedAppointment = new Appointment();
        $refusedAppointment->setDate(new DateTime('2026-03-03 14:00:00'));
        $refusedAppointment->setStatus('refused');
        $refusedAppointment->setPsychologue($this->psychologistUser);

        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$refusedAppointment]);

        // Try to book at same time
        $requestedDate = new DateTime('2026-03-03 14:00:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate);

        $this->assertFalse($result, 'Refused appointments should not create conflicts');
    }

    public function testExcludeAppointmentIdInConflictCheck(): void
    {
        // Create an appointment with ID 1
        $existingAppointment = new Appointment();
        $existingAppointment->setDate(new DateTime('2026-03-03 14:00:00'));
        $existingAppointment->setStatus('accepted');
        $existingAppointment->setPsychologue($this->psychologistUser);
        // Use reflection to set ID
        $reflection = new \ReflectionClass($existingAppointment);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($existingAppointment, 1);

        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$existingAppointment]);

        // Try to book at same time but exclude appointment 1 (editing scenario)
        $requestedDate = new DateTime('2026-03-03 14:00:00');
        $result = $this->manager->hasTimeSlotConflict($this->psychologistUser, $requestedDate, 1);

        $this->assertFalse($result, 'Should exclude the specified appointment ID from conflict check');
    }

    // ============================================================================
    // RULE #4: Status Lifecycle Automation Tests
    // ============================================================================

    public function testAcceptedStatusShouldTransitionToInProgressWhenTimeArrives(): void
    {
        // Create appointment that started 5 minutes ago
        $appointment = new Appointment();
        $pastDate = (new DateTime())->modify('-5 minutes');
        $appointment->setDate($pastDate);
        $appointment->setStatus('accepted');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertEquals('in_progress', $nextStatus, 'Should transition to in_progress when appointment time arrives');
    }

    public function testAcceptedStatusShouldNotTransitionBeforeTime(): void
    {
        // Create appointment that starts in 1 hour
        $appointment = new Appointment();
        $futureDate = (new DateTime())->modify('+1 hour');
        $appointment->setDate($futureDate);
        $appointment->setStatus('accepted');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertNull($nextStatus, 'Should not transition when appointment time has not arrived yet');
    }

    public function testInProgressStatusShouldTransitionToCompletedAfter1Hour(): void
    {
        // Create appointment that started 1 hour and 5 minutes ago
        $appointment = new Appointment();
        $pastDate = (new DateTime())->modify('-65 minutes');
        $appointment->setDate($pastDate);
        $appointment->setStatus('in_progress');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertEquals('completed', $nextStatus, 'Should transition to completed 1 hour after start');
    }

    public function testInProgressStatusShouldNotTransitionBefore1Hour(): void
    {
        // Create appointment that started 30 minutes ago
        $appointment = new Appointment();
        $pastDate = (new DateTime())->modify('-30 minutes');
        $appointment->setDate($pastDate);
        $appointment->setStatus('in_progress');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertNull($nextStatus, 'Should not transition to completed before 1 hour has passed');
    }

    public function testCompletedStatusShouldTransitionToArchivedAfter30Days(): void
    {
        // Create appointment that started 31 days ago
        $appointment = new Appointment();
        $veryOldDate = (new DateTime())->modify('-31 days');
        $appointment->setDate($veryOldDate);
        $appointment->setStatus('completed');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertEquals('archived', $nextStatus, 'Should transition to archived after 30 days');
    }

    public function testCompletedStatusShouldNotTransitionBefore30Days(): void
    {
        // Create appointment that started 25 days ago
        $appointment = new Appointment();
        $recentDate = (new DateTime())->modify('-25 days');
        $appointment->setDate($recentDate);
        $appointment->setStatus('completed');

        $nextStatus = $this->manager->shouldAutoUpdateStatus($appointment);

        $this->assertNull($nextStatus, 'Should not transition to archived before 30 days have passed');
    }

    // ============================================================================
    // Rule #4 (continued): Valid Status Transitions Tests
    // ============================================================================

    public function testValidTransitionFromPendingToAccepted(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('pending');

        $result = $this->manager->canTransitionTo($appointment, 'accepted');

        $this->assertTrue($result, 'pending → accepted should be valid');
    }

    public function testValidTransitionFromPendingToRefused(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('pending');

        $result = $this->manager->canTransitionTo($appointment, 'refused');

        $this->assertTrue($result, 'pending → refused should be valid');
    }

    public function testValidTransitionFromAcceptedToInProgress(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('accepted');

        $result = $this->manager->canTransitionTo($appointment, 'in_progress');

        $this->assertTrue($result, 'accepted → in_progress should be valid');
    }

    public function testValidTransitionFromInProgressToCompleted(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('in_progress');

        $result = $this->manager->canTransitionTo($appointment, 'completed');

        $this->assertTrue($result, 'in_progress → completed should be valid');
    }

    public function testValidTransitionFromCompletedToArchived(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('completed');

        $result = $this->manager->canTransitionTo($appointment, 'archived');

        $this->assertTrue($result, 'completed → archived should be valid');
    }

    public function testInvalidTransitionFromCompletedToPending(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('completed');

        $result = $this->manager->canTransitionTo($appointment, 'pending');

        $this->assertFalse($result, 'completed → pending should be invalid (cannot go backwards)');
    }

    public function testInvalidTransitionFromRefusedToAccepted(): void
    {
        $appointment = new Appointment();
        $appointment->setStatus('refused');

        $result = $this->manager->canTransitionTo($appointment, 'accepted');

        $this->assertFalse($result, 'refused → accepted should be invalid');
    }

    public function testCancelledIsAlwaysValidTransition(): void
    {
        // Test from various states
        foreach (['pending', 'accepted', 'in_progress', 'completed', 'archived'] as $status) {
            $appointment = new Appointment();
            $appointment->setStatus($status);

            $result = $this->manager->canTransitionTo($appointment, 'cancelled');

            $this->assertTrue($result, "$status → cancelled should always be valid");
        }
    }

    // ============================================================================
    // RULE #5: Future Date Validation Tests
    // ============================================================================

    public function testIsDateInFutureWithValidFutureDate(): void
    {
        $futureDate = (new DateTime())->modify('+2 hours');
        $result = $this->manager->isDateInFuture($futureDate);

        $this->assertTrue($result, 'Date 2 hours in future should be valid');
    }

    public function testCannotBookLessThan1HourFromNow(): void
    {
        $almostNow = (new DateTime())->modify('+30 minutes');
        $result = $this->manager->isDateInFuture($almostNow);

        $this->assertFalse($result, 'Cannot book less than 1 hour from now');
    }

    public function testCannotBookInThePast(): void
    {
        $pastDate = (new DateTime())->modify('-1 day');
        $result = $this->manager->isDateInFuture($pastDate);

        $this->assertFalse($result, 'Cannot book in the past');
    }

    public function testExactlyOneHourFromNowIsValid(): void
    {
        $exactlyOneHour = (new DateTime())->modify('+1 hour +1 second');
        $result = $this->manager->isDateInFuture($exactlyOneHour);

        // Add 1 second buffer to account for execution time
        $this->assertTrue($result, 'Exactly 1 hour from now (with buffer) should be valid');
    }

    // ============================================================================
    // RULE #6: Online Appointment Auto-Setup Tests
    // ============================================================================

    public function testShouldCreateZoomMeetingForOnlineAppointmentBecomingAccepted(): void
    {
        $appointment = new Appointment();
        $appointment->setLocation('online');
        $appointment->setStatus('accepted');
        $appointment->setZoomMeetingId(null);

        $result = $this->manager->shouldCreateZoomMeeting($appointment, 'pending');

        $this->assertTrue($result, 'Should create Zoom meeting when online appointment is accepted');
    }

    public function testShouldNotCreateZoomForInOfficeAppointment(): void
    {
        $appointment = new Appointment();
        $appointment->setLocation('in_office');
        $appointment->setStatus('accepted');
        $appointment->setZoomMeetingId(null);

        $result = $this->manager->shouldCreateZoomMeeting($appointment, 'pending');

        $this->assertFalse($result, 'Should not create Zoom for in-office appointments');
    }

    public function testShouldNotCreateZoomIfAlreadyExists(): void
    {
        $appointment = new Appointment();
        $appointment->setLocation('online');
        $appointment->setStatus('accepted');
        $appointment->setZoomMeetingId('123456');

        $result = $this->manager->shouldCreateZoomMeeting($appointment, 'pending');

        $this->assertFalse($result, 'Should not create Zoom if meeting ID already exists');
    }

    public function testShouldNotCreateZoomIfNotBecomingAccepted(): void
    {
        $appointment = new Appointment();
        $appointment->setLocation('online');
        $appointment->setStatus('in_progress');
        $appointment->setZoomMeetingId(null);

        $result = $this->manager->shouldCreateZoomMeeting($appointment, 'accepted');

        $this->assertFalse($result, 'Should not create Zoom if status change is not pending→accepted');
    }

    // ============================================================================
    // Comprehensive Validation Tests
    // ============================================================================

    public function testValidateAppointmentWithValidData(): void
    {
        $appointment = new Appointment();
        $appointment->setDate((new DateTime())->modify('+2 hours'));

        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(false);
        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([]);

        $errors = $this->manager->validateAppointment(
            $appointment,
            $this->studentUser,
            $this->psychologistUser
        );

        $this->assertEmpty($errors, 'Should have no errors for valid appointment');
    }

    public function testValidateAppointmentWithPastDate(): void
    {
        $appointment = new Appointment();
        $appointment->setDate((new DateTime())->modify('-1 day'));

        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(false);
        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([]);

        $errors = $this->manager->validateAppointment(
            $appointment,
            $this->studentUser,
            $this->psychologistUser
        );

        $this->assertNotEmpty($errors, 'Should have errors for past date');
        $this->assertStringContainsString('1 hour', $errors[0], 'Should mention 1 hour requirement');
    }

    public function testValidateAppointmentWithConflict(): void
    {
        $existingAppointment = new Appointment();
        $conflictDate = new DateTime('2026-03-03 14:00:00');
        $existingAppointment->setDate($conflictDate);
        $existingAppointment->setStatus('accepted');

        $appointment = new Appointment();
        $appointment->setDate($conflictDate);

        $this->appointmentRepositoryMock->method('hasAppointmentThisWeekWithPsychologue')
            ->willReturn(false);
        $this->appointmentRepositoryMock->method('findBy')
            ->willReturn([$existingAppointment]);

        $errors = $this->manager->validateAppointment(
            $appointment,
            $this->studentUser,
            $this->psychologistUser
        );

        $this->assertNotEmpty($errors, 'Should have errors for time slot conflict');
    }
}
