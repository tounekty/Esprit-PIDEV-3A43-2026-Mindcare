<?php

namespace App\MessageHandler;

use App\Entity\Mood;
use App\Message\GenerateMoodPdfMessage;
use App\Repository\MoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final class GenerateMoodPdfMessageHandler
{
    public function __construct(
        private MoodRepository $moodRepository,
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function __invoke(GenerateMoodPdfMessage $message): void
    {
        $mood = $this->moodRepository->find($message->getMoodId());
        
        if (!$mood) {
            return;
        }

        try {
            // Generate PDF filename
            $fileName = sprintf(
                'mood_%s_%d.pdf',
                $mood->getDatemood()->format('Y-m-d'),
                $mood->getId()
            );
            
            $pdfPath = $this->getPdfDirectory() . DIRECTORY_SEPARATOR . $fileName;
            
            // Decode AI analysis if available
            $aiAnalysis = null;
            if ($mood->getAiAnalysis()) {
                $aiAnalysis = json_decode($mood->getAiAnalysis(), true);
            }
            
            // Render Twig template to HTML
            $html = $this->twig->render('mood/pdf.html.twig', [
                'mood' => $mood,
                'ai_analysis' => $aiAnalysis,
            ]);
            
            // Generate PDF using Dompdf
            $this->generatePdfFromHtml($html, $pdfPath);
            
            // Store PDF path in mood
            $mood->setPdfPath($fileName);
            
            $this->entityManager->persist($mood);
            $this->entityManager->flush();
            
            $this->logger->info('PDF generated successfully', ['mood_id' => $mood->getId(), 'file' => $fileName]);
        } catch (\Exception $e) {
            // Log error but don't fail the message
            $this->logger->error('PDF Generation Error: ' . $e->getMessage(), [
                'mood_id' => $message->getMoodId(),
                'exception' => $e
            ]);
        }
    }

    private function generatePdfFromHtml(string $html, string $filePath): void
    {
        if (!class_exists('Dompdf\Dompdf')) {
            throw new \RuntimeException('Dompdf is not installed. Run: composer require dompdf/dompdf');
        }

        $dompdf = new \Dompdf\Dompdf();
        
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
        
        file_put_contents($filePath, $dompdf->output());
    }

    private function getPdfDirectory(): string
    {
        $dir = $this->projectDir . '/public/uploads/moods_pdf';
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir;
    }
}

