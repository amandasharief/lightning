<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Controller\TestApp;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\AbstractController;

class ArticlesController extends AbstractController
{
    public function index(): ResponseInterface
    {
        return $this->render('articles/index', [
            'title' => 'Articles'
        ]);
    }

    public function status($payload, int $statusCode = 200): ResponseInterface
    {
        return $this->renderJson($payload, $statusCode);
    }

    public function old(string $uri): ResponseInterface
    {
        return $this->redirect($uri);
    }

    public function download(string $path, array $options = []): ResponseInterface
    {
        return $this->renderFile($path, $options);
    }

    public function createResponse(): ResponseInterface
    {
        return new Response();
    }
}
