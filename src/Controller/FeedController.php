<?php

namespace App\Controller;

use App\Entity\Association;
use App\Feed\FeedFilters;
use App\Repository\AssociationRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * RSS feeds, documented in RSS.md.
 */
#[Route('/rss')]
class FeedController extends AbstractController
{
    /**
     * OPML list of the available feeds (the feeds equivalent of a sitemap index).
     */
    #[Route('', name: 'feed_index')]
    public function index(): Response
    {
        return $this->rss('feed/index.opml.twig', [], 'application/xml');
    }

    #[Route('/associations.xml', name: 'feed_associations')]
    public function associations(Request $request, AssociationRepository $associationRepository): Response
    {
        $query = $request->query->all();
        $errors = [];
        $categories = FeedFilters::parseCategories($query, $errors);
        [$sortField, $sortDirection] = FeedFilters::parseSort($query, FeedFilters::ASSOCIATION_SORT_FIELDS, ['updatedAt', 'DESC'], $errors);

        if (FeedFilters::isDebug($query)) {
            return $this->debug(['categories' => $categories, 'sort' => $sortField.','.$sortDirection], $errors);
        }

        return $this->rss('feed/associations.xml.twig', [
            'associations' => $associationRepository->findForFeed($categories, $sortField, $sortDirection),
        ]);
    }

    #[Route('/evenements.xml', name: 'feed_events')]
    public function events(Request $request, EventRepository $eventRepository, AssociationRepository $associationRepository): Response
    {
        $query = $request->query->all();
        $filters = FeedFilters::forEvents($query, new \DateTimeImmutable());

        // Unknown slugs are ignored; if none is known, the filter is ignored altogether.
        $associations = $filters->associationSlugs ? $associationRepository->findBy(['slug' => $filters->associationSlugs]) : [];
        $foundSlugs = array_map(fn (Association $association) => $association->getSlug(), $associations);
        foreach (array_diff($filters->associationSlugs, $foundSlugs) as $slug) {
            $filters->errors[] = sprintf('association : « %s » inconnue', $slug);
        }

        if (FeedFilters::isDebug($query)) {
            return $this->debug(['association' => $foundSlugs] + $filters->applied(), $filters->errors);
        }

        return $this->rss('feed/events.xml.twig', [
            'events' => $eventRepository->findForFeed($filters, $associations),
        ]);
    }

    private function rss(string $template, array $parameters, string $contentType = 'application/rss+xml'): Response
    {
        $response = $this->render($template, $parameters);
        $response->headers->set('Content-Type', $contentType.'; charset=UTF-8');

        return $response->setPublic()->setMaxAge(3600);
    }

    private function debug(array $applied, array $errors): Response
    {
        return new Response(FeedFilters::describe($applied, $errors), Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
