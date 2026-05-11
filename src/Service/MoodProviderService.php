<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Mood;
use App\Repository\MoodRepository;
use Doctrine\ORM\EntityManagerInterface;

class MoodProviderService
{
    public function __construct(
        private MoodRepository $moodRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Get or create a mood for the given user and mood type
     */
    public function getOrCreateMood(User $user, string $moodType): ?Mood
    {
        // Try to find an existing mood for this user with this emotion type
        $mood = $this->moodRepository->findOneBy([
            'user' => $user,
            'humeur' => $moodType
        ]);

        if (!$mood) {
            // Create a new mood
            $mood = new Mood();
            $mood->setUser($user);
            $mood->setHumeur($moodType);
            $mood->setIntensite(3); // Default intensity
            $mood->setDatemood(new \DateTime());
            
            $this->entityManager->persist($mood);
            $this->entityManager->flush();
        } else {
            // Update the date to today
            $mood->setDatemood(new \DateTime());
            $this->entityManager->flush();
        }

        return $mood;
    }
}
