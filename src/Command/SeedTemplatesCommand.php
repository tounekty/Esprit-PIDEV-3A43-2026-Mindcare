<?php

namespace App\Command;

use App\Entity\EntryTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-templates',
    description: 'Create default entry templates',
)]
class SeedTemplatesCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $templates = [
            [
                'title' => 'Qu\'est-ce qui vous a rendu heureux aujourd\'hui ?',
                'prompt' => 'Pensez à un moment qui vous a fait sourire. Décrivez ce moment et pourquoi cela vous a rendu heureux.',
                'category' => 'gratitude',
                'description' => 'Focalisez-vous sur les moments positifs',
                'order' => 1
            ],
            [
                'title' => 'Votre plus grand défi aujourd\'hui',
                'prompt' => 'Décrivez le principal défi que vous avez rencontré. Comment l\'avez-vous géré ? Qu\'avez-vous appris ?',
                'category' => 'challenge',
                'description' => 'Réfléchissez sur les défis et les apprentissages',
                'order' => 2
            ],
            [
                'title' => 'Réflexion personnelle du jour',
                'prompt' => 'Comment vous sentez-vous vraiment en ce moment ? Qu\'est-ce qui occupe votre esprit ?',
                'category' => 'reflection',
                'description' => 'Explorez vos pensées et émotions',
                'order' => 3
            ],
            [
                'title' => '3 Choses pour lesquelles vous êtes reconnaissant',
                'prompt' => 'Énumérez trois choses pour lesquelles vous êtes reconnaissant, peu importe leur taille.',
                'category' => 'gratitude',
                'description' => 'Pratiquez la gratitude quotidienne',
                'order' => 4
            ],
            [
                'title' => 'Vos objectifs pour demain',
                'prompt' => 'Qu\'aimeriez-vous accomplir demain ? Quels sont vos objectifs prioritaires ?',
                'category' => 'inspiration',
                'description' => 'Planifiez votre avenir avec intention',
                'order' => 5
            ],
            [
                'title' => 'Comment prendre soin de vous',
                'prompt' => 'Qu\'allez-vous faire pour prendre soin de vous aujourd\'hui ? (physiquement, émotionnellement, mentalement)',
                'category' => 'inspiration',
                'description' => 'Mettez l\'accent sur l\'auto-soins',
                'order' => 6
            ],
            [
                'title' => 'Leçons apprises récemment',
                'prompt' => 'Quelle leçon importante avez-vous apprise récemment ? Comment cela change votre perspective ?',
                'category' => 'reflection',
                'description' => 'Capturez les moments d\'apprentissage profond',
                'order' => 7
            ],
            [
                'title' => 'Messages pour votre futur moi',
                'prompt' => 'Que voudriez-vous dire à votre futur moi (dans un mois, un an) ? Quels conseils donneriez-vous ?',
                'category' => 'inspiration',
                'description' => 'Créez des messages motivants',
                'order' => 8
            ],
        ];

        foreach ($templates as $data) {
            $template = new EntryTemplate();
            $template->setTitle($data['title']);
            $template->setPrompt($data['prompt']);
            $template->setCategory($data['category']);
            $template->setDescription($data['description']);
            $template->setDisplayOrder($data['order']);
            $template->setIsActive(true);
            
            $this->entityManager->persist($template);
            $io->text('Création du template: ' . $data['title']);
        }

        $this->entityManager->flush();
        $io->success('Templates créés avec succès!');

        return Command::SUCCESS;
    }
}
