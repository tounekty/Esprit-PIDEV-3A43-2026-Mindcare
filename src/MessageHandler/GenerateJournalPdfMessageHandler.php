<?php

namespace App\MessageHandler;

use App\Message\GenerateJournalPdfMessage;
use App\Repository\JournalEmotionnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Twig\Environment;

#[AsMessageHandler]
final class GenerateJournalPdfMessageHandler
{
    public function __construct(
        private JournalEmotionnelRepository $journalRepository,
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function __invoke(GenerateJournalPdfMessage $message): void
    {
        try {
            $journal = $this->journalRepository->find($message->getJournalId());
            
            if (!$journal) {
                throw new UnrecoverableMessageHandlingException('Journal not found');
            }

            // Decode mood AI analysis if available
            $moodAnalysis = null;
            if ($journal->getMood() && $journal->getMood()->getAiAnalysis()) {
                $moodAnalysis = json_decode($journal->getMood()->getAiAnalysis(), true);
            }

            // Render PDF template
            $html = $this->twig->render('journal/pdf.html.twig', [
                'journal' => $journal,
                'mood_analysis' => $moodAnalysis,
            ]);

            // Generate PDF
            $dompdf = new Dompdf();
            
            // Configure Dompdf with proper base path for images and CSS
            $options = $dompdf->getOptions();
            $publicPath = realpath($this->projectDir . '/public');
            $cachePath = realpath($this->projectDir . '/var/cache/dompdf');
            
            $options->setChroot($publicPath);
            $options->setIsHtml5ParserEnabled(true);
            if (is_dir($cachePath)) {
                $options->setFontCache($cachePath);
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Create directory if it doesn't exist
            $pdfDir = $this->projectDir . '/public/uploads/journal_pdf';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            // Save PDF
            $fileName = 'journal_' . date('Y-m-d') . '_' . $message->getJournalId() . '.pdf';
            $filePath = $pdfDir . '/' . $fileName;
            file_put_contents($filePath, $dompdf->output());

            // Update journal with PDF path
            $journal->setPdfPath($fileName);
            $this->entityManager->persist($journal);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log error but don't fail the message
            error_log('Error generating journal PDF: ' . $e->getMessage());
        }
    }
}

