<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Controller\TestApp;

use Lightning\Controller\AbstractController;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;


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
