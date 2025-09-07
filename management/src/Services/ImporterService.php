<?php

namespace Scacchilatorre\Management\Services;

use DateTime;
use DOMDocument;
use DOMXPath;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImporterService implements IOInterface
{
    public function __construct(
        private readonly DbService $dbService,
    )
    {
    }

    private SymfonyStyle $io;

    public function import(string $dumpPath): void
    {
        $this->io->info('Import started');

        $this->dbService->resetDb();
        $this->io->writeln('- Database has been reset');

        if (!is_dir($dumpPath)) {
            $this->io->error('Dir not found: ' . $dumpPath);
            return;
        }

        $dirIterator = new RecursiveDirectoryIterator($dumpPath);
        $path = $dirIterator->getPath();
        $dirIterator->setFlags(FilesystemIterator::CURRENT_AS_SELF);
        $this->io->writeln('- Scanning ' . $path . '...');

        $fileIterator = new RecursiveIteratorIterator($dirIterator);
        $fileList = new RegexIterator($fileIterator, '/^(\d{4}|index)\.php$/');

        $categoriesCache = [];
        $connection = $this->dbService->getConnection();
        $stmtPosts = $connection->prepare("INSERT INTO articles (author_id, title, content, created_at) VALUES (?, ?, ?, ?)");
        $stmtCategories = $connection->prepare("INSERT INTO categories (name, created_at) VALUES (?, CURRENT_TIMESTAMP)");
        $stmtPostsCategories = $connection->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");

        try {
            foreach($fileList as $file) {
                $year = (int)substr($file, 0, 4);

                if (($year >= 2012 && $year <= 2024) || $year === 0) {
                    $this->io->writeln("- Importing $file...");
                    $content = $this->getFileContent(join(DIRECTORY_SEPARATOR, [$path, $file]));
                    $posts = $this->extractPostsFromHtml($content);
                    $this->io->writeln('- Found ' . count($posts) . ' posts');

                    foreach ($posts as $post) {
                        $stmtPosts->execute(
                            [1, $post['title'], $post['content'], $post['creation_date'] ?? '0000-00-00 00:00:00']
                        );
                        $postId = $connection->lastInsertId();
                        $categories = $this->deriveCategories($post['title'] . ' ' . $post['content']);
                        $this->io->writeln('    - ' . $post['title'] . ' - ' . json_encode($categories));

                        foreach ($categories as $category) {
                            if (!in_array($category, array_keys($categoriesCache))) {
                                $stmtCategories->execute([$category]);
                                $categoryId = $connection->lastInsertId();
                                $categoriesCache[$category] = $categoryId;
                            } else {
                                $categoryId = $categoriesCache[$category];
                            }

                            $stmtPostsCategories->execute([$postId, $categoryId]);
                        }
                    }
                } else {
                    $this->io->writeln('- Skipping ' . $year . ' in ' . $file);
                }
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        } finally {
            $stmtPosts = null;
            $stmtCategories = null;
            $stmtPostsCategories = null;
            $connection = null;
        }
    }

    public function withIO(SymfonyStyle $io): IOInterface
    {
        $this->io = $io;
        return $this;
    }

    public function getFileContent($filename): string
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            $this->io->error('Error: cannot read file ' . $filename);
            exit(1);
        }

        return preg_replace('/<\?php.*?\?>/s', '', $content);
    }

    function extractPostsFromHtml($htmlContent) {
        libxml_use_internal_errors(true); // Suppress DOM warnings for malformed HTML
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $tds = $xpath->query('//td');
        $posts = [];

        // Map Italian month names to their corresponding numerical values
        $months = [
            'gennaio' => '01',
            'febbraio' => '02',
            'marzo' => '03',
            'aprile' => '04',
            'maggio' => '05',
            'giugno' => '06',
            'luglio' => '07',
            'agosto' => '08',
            'settembre' => '09',
            'ottobre' => '10',
            'novembre' => '11',
            'dicembre' => '12',
        ];

        foreach ($tds as $td) {
            $children = $td->childNodes;
            $currentPost = null;

            foreach ($children as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'div') {
                    $class = $child->getAttribute('class');

                    if ($class === 'separator') {
                        if ($currentPost && isset($currentPost['title'])) {
                            // Join all parts of content into one HTML string
                            $currentPost['content'] = implode('', $currentPost['content']);
                            $posts[] = $currentPost;
                            $currentPost = null;
                        }
                        continue;
                    }

                    if ($class === 'newstitle') {
                        if ($currentPost && isset($currentPost['title'])) {
                            // If another title appears without separator, treat previous post as complete
                            $currentPost['content'] = implode('', $currentPost['content']);
                            $posts[] = $currentPost;
                        }

                        $currentPost = [
                            'title' => trim($child->textContent),
                            'creation_date' => null,
                            'content' => []
                        ];
                    } elseif ($class === 'newsdate' && $currentPost !== null) {
                        // Split the date into components
                        $dateParts = explode(' ', trim($child->textContent));
                        if (count($dateParts) === 3) {
                            $day = $dateParts[0];
                            $month = strtolower($dateParts[1]); // Convert month to lowercase
                            $year = $dateParts[2];

                            // Get the month number from the mapping
                            if (isset($months[$month])) {
                                $monthNumber = $months[$month];
                                // Create DateTime object
                                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', "$year-$monthNumber-$day 00:00:00");
                                if ($dateTime) {
                                    $currentPost['creation_date'] = $dateTime->format('Y-m-d H:i:s');
                                }
                            }
                        }
                    } elseif ($class === 'newstext' && $currentPost !== null) {
                        $html = $dom->saveHTML($child);
                        $currentPost['content'][] = $html;
                    }
                }
            }

            // In case the last post is not followed by a separator
            if ($currentPost && isset($currentPost['title'])) {
                $currentPost['content'] = implode('', $currentPost['content']);
                $posts[] = $currentPost;
            }
        }

        return $posts;
    }

    public function deriveCategories(string $htmlContent)
    {
        // Define categories and tags based on keywords
        $categories = [
            "Assemblee" => ['assemblea'],
            "Tornei" => ['campionato', 'tornei', 'torneino', 'torneo', 'slam', 'minislam', 'maratona blitz', 'Semilampo', 'provinciale', 'regionale', 'festival'],
            "Attività" => ['Pisa in gioco', 'Notte bianca', 'corsi', 'corso', 'scuola di scacchi', 'beach chess', 'iniziativa', 'conferenza', 'PisaCON', 'simultanea', 'Ingresso libero'],
            "Comunicazioni" => ['riapertura', 'chiusura', 'sospensione', 'ritrovo settimanale', 'incontro settimanale', 'ripresa attività', 'Riprendono gli incontri', 'assemblea', 'convocazione', 'nuova sede'],
            "Tesseramento" => ['tesseramento']
        ];

        // Extract text from HTML
        $text = strip_tags($htmlContent);
        $text = strtolower($text); // Normalize to lowercase

        $keywordCounts = [];

        // Count keywords for each category
        foreach ($categories as $category => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $count++;
                }
            }
            if ($count > 0) {
                $keywordCounts[$category] = $count;
            }
        }

        // Determine the category with the most keywords
        $derivedCategories = [];
        if (!empty($keywordCounts)) {
            $maxCount = max($keywordCounts);
            foreach ($keywordCounts as $category => $count) {
                if ($count === $maxCount) {
                    $derivedCategories[] = $category; // Include all categories with the max count
                }
            }
        }

        return array_unique($derivedCategories);
    }
}
