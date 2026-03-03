<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use DateTime;
use DateTimeInterface;

/**
 * Business logic manager for Appointment entity
 * Handles all business rules and validations
 */
class AppointmentBusinessManager
{
    private const APPOINTMENT_DURATION_MINUTES = 60;
    private const DEFAULT_TIMEZONE = 'UTC';

    public function __construct(
        private AppointmentRepository $appointmentRepository
    ) {
    }

    /**
     * Rule #1: Check if student can book another appointment this week with psychologist
     * Student can book maximum 1 appointment per week with same psychologist
     * 
     * @param User $student The student trying to book
     * @param User $psychologist The psychologist being booked
     * @param DateTimeInterface $appointmentDate The date of the appointment
     * @return bool True if student can book, False if limit reached
     */
    public function canStudentBookThisWeek(
        User $student,
        User $psychologist,
        DateTimeInterface $appointmentDate
    ): bool {
        return !$this->appointmentRepository->hasAppointmentThisWeekWithPsychologue(
            $student,
            $psychologist,
            $appointmentDate
        );
    }

    /**
     * Rule #3: Check for time slot conflicts with psychologist's existing appointments
     * Cannot book a time when psychologist already has appointment
     * 
     * @param User $psychologist The psychologist
     * @param DateTimeInterface $appointmentDate The requested appointment date
     * @param ?int $excludeAppointmentId Appointment ID to exclude (for edits)
     * @return bool True if slot is available, False if conflict exists
     */
    public function hasTimeSlotConflict(
        User $psychologist,
        DateTimeInterface $appointmentDate,
        ?int $excludeAppointmentId = null
    ): bool {
        $appointmentEnd = $this->getAppointmentEndTime($appointmentDate);
        $existingAppointments = $this->appointmentRepository->findBy([
            'psychologue' => $psychologist
        ]);

        foreach ($existingAppointments as $existing) {
            // Skip excluded appointment (for editing)
            if ($excludeAppointmentId && $existing->getId() === $excludeAppointmentId) {
                continue;
            }

            // Ignore refused appointments
            if ($existing->getStatus() === 'refused') {
                continue;
            }

            $existingStart = $existing->getDate();
            $existingEnd = $this->getAppointmentEndTime($existingStart);

            // Check overlap: requested time starts before existing ends AND starts after existing starts
            if ($appointmentDate >= $existingStart && $appointmentDate < $existingEnd) {
                return true; // Conflict found
            }

            // Also check if existing starts during requested appointment
            if ($existingStart >= $appointmentDate && $existingStart < $appointmentEnd) {
                return true; // Conflict found
            }
        }

        return false; // No conflict
    }

    /**
     * Rule #2: Calculate appointment end time (fixed 1 hour duration)
     * 
     * @param DateTimeInterface $startDate The appointment start date/time
     * @return DateTime The appointment end time (start + 1 hour)
     */
    public function getAppointmentEndTime(DateTimeInterface $startDate): DateTime
    {
        return (clone $startDate)->modify('+' . self::APPOINTMENT_DURATION_MINUTES . ' minutes');
    }

    /**
     * Rule #5: Validate that appointment date is in the future (at least 1 hour from now)
     * 
     * @param DateTimeInterface $appointmentDate The appointment date to validate
     * @return bool True if date is valid (future), False if invalid (past)
     */
    public function isDateInFuture(DateTimeInterface $appointmentDate): bool
    {
        $now = new DateTime('now', new \DateTimeZone(self::DEFAULT_TIMEZONE));
        $minimumDate = (clone $now)->modify('+1 hour');

        return $appointmentDate >= $minimumDate;
    }

    /**
     * Rule #4: Determine if appointment should auto-transition to next status
     * Based on time progression:
     * - accepted → in_progress (when appointment time arrives)
     * - in_progress → completed (1 hour after start)
     * - completed → archived (30 days after completion)
     * 
     * @param Appointment $appointment The appointment to check
     * @return ?string The next status if transition should occur, null if no transition
     */
    public function shouldAutoUpdateStatus(Appointment $appointment): ?string
    {
        $now = new DateTime('now', new \DateTimeZone(self::DEFAULT_TIMEZONE));
        $currentStatus = $appointment->getStatus();
        $appointmentDate = $appointment->getDate();

        if (!$appointmentDate) {
            return null;
        }

        // Case 1: pending/accepted → in_progress (when appointment time arrives)
        if ($currentStatus === 'accepted' && $appointmentDate <= $now) {
            return 'in_progress';
        }

        // Case 2: in_progress → completed (1 hour after start)
        if ($currentStatus === 'in_progress') {
            $appointmentEnd = $this->getAppointmentEndTime($appointmentDate);
            if ($appointmentEnd <= $now) {
                return 'completed';
            }
        }

        // Case 3: completed → archived (30 days after start of appointment)
        if ($currentStatus === 'completed') {
            $archiveDate = (clone $appointmentDate)->modify('+30 days');
            if ($archiveDate <= $now) {
                return 'archived';
            }
        }

        return null; // No transition needed
    }

    /**
     * Rule #4: Check if a status transition is valid
     * Valid transitions:
     * - pending → accepted
     * - pending → refused
     * - accepted → in_progress
     * - in_progress → completed
     * - completed → archived
     * - * → cancelled (always allowed)
     * 
     * @param Appointment $appointment The appointment
     * @param string $newStatus The desired new status
     * @return bool True if transition is valid, False otherwise
     */
    public function canTransitionTo(Appointment $appointment, string $newStatus): bool
    {
        $currentStatus = $appointment->getStatus();

        // Define valid state transitions
        $validTransitions = [
            'pending' => ['accepted', 'refused', 'cancelled'],
            'accepted' => ['in_progress', 'refused', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => ['archived', 'cancelled'],
            'archived' => ['cancelled'],
            'refused' => [],
            'cancelled' => [],
        ];

        // Check if transition exists in valid transitions
        if (!isset($validTransitions[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, $validTransitions[$currentStatus], true);
    }

    /**
     * Rule #6: Check if online appointment needs Zoom meeting setup
     * Returns true when appointment is online and just accepted
     * 
     * @param Appointment $appointment The appointment
     * @param string $previousStatus The status before the change
     * @return bool True if Zoom should be created, False otherwise
     */
    public function shouldCreateZoomMeeting(Appointment $appointment, string $previousStatus = 'pending'): bool
    {
        return $appointment->getLocation() === 'online'
            && $previousStatus === 'pending'
            && $appointment->getStatus() === 'accepted'
            && !$appointment->getZoomMeetingId();
    }

    /**
     * Get the duration of an appointment in minutes
     * 
     * @return int Duration in minutes
     */
    public function getAppointmentDurationMinutes(): int
    {
        return self::APPOINTMENT_DURATION_MINUTES;
    }

    /**
     * Validate all business rules for appointment creation/update
     * Returns array of validation errors
     * 
     * @param Appointment $appointment The appointment to validate
     * @param User $student The student
     * @param User $psychologist The psychologist
     * @param ?int $excludeAppointmentId For edit operations
     * @return array Array of error messages, empty if valid
     */
    public function validateAppointment(
        Appointment $appointment,
        User $student,
        User $psychologist,
        ?int $excludeAppointmentId = null
    ): array {
        $errors = [];
        $appointmentDate = $appointment->getDate();

        if (!$appointmentDate) {
            $errors[] = 'Appointment date is required';
            return $errors;
        }

        // Rule #5: Check future date
        if (!$this->isDateInFuture($appointmentDate)) {
            $errors[] = 'Appointment must be at least 1 hour from now';
        }

        // Rule #1: Check weekly limit (exclude if editing)
        if (!$excludeAppointmentId && !$this->canStudentBookThisWeek($student, $psychologist, $appointmentDate)) {
            $errors[] = 'Student already has an appointment with this psychologist this week. Maximum 1 per week.';
        }

        // Rule #3: Check time slot availability
        if ($this->hasTimeSlotConflict($psychologist, $appointmentDate, $excludeAppointmentId)) {
            $errors[] = 'This time slot is not available. Please choose another date.';
        }

        return $errors;
    }
}
