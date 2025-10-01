<?php
declare(strict_types=1);

namespace KaufmannDigital\DomainRedirection\Http;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class DomainRedirectMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\InjectConfiguration(path="redirects", package="KaufmannDigital.DomainRedirection")
     * @var array
     */
    protected $redirectConfiguration;

    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();
        $path = $request->getUri()->getPath();
        $hostWithPath = $host . $path;

        if (!empty($this->redirectConfiguration)) {
            foreach ($this->redirectConfiguration as $redirect) {
                if (!isset($redirect['domainPattern'])) {
                    continue;
                }

                // Check if domain+path matches (either as regex or plain string)
                $domainPattern = $redirect['domainPattern'];
                $domainMatches = (@preg_match('#' . $domainPattern . '#', $hostWithPath) === 1) || ($host === $domainPattern);

                if ($domainMatches) {
                    // Check for regex rules
                    if (isset($redirect['rules']) && is_array($redirect['rules'])) {
                        foreach ($redirect['rules'] as $rule) {
                            if (isset($rule['pattern']) && preg_match('#' . $rule['pattern'] . '#', $path)) {
                                $replacement = $rule['replacement'] ?? '/';
                                $statusCode = $rule['statusCode'] ?? 301;

                                // Check if replacement is a full URL or just a path
                                if (preg_match('#^https?://#', $replacement)) {
                                    $targetUrl = preg_replace('#' . $rule['pattern'] . '#', $replacement, $path);
                                } else {
                                    // If replacement is a path, build full URL from target domain
                                    $targetPath = preg_replace('#' . $rule['pattern'] . '#', $replacement, $path);
                                    $targetUrl = isset($redirect['target']) ? rtrim($redirect['target'], '/') . $targetPath : $targetPath;
                                }

                                return $this->createRedirectResponse($targetUrl, $statusCode);
                            }
                        }
                    }

                    // Default domain redirect if no rule matched
                    if (isset($redirect['target'])) {
                        $statusCode = $redirect['statusCode'] ?? 301;

                        if (isset($redirect['pattern'])) {
                            $targetUrl = preg_replace('#' . $redirect['pattern'] . '#', $redirect['target'], $path);
                        } else {
                            // Check if domainPattern contains regex (has capturing groups or regex special chars)
                            if (@preg_match('#' . $domainPattern . '#', $hostWithPath) === 1 && strpos($domainPattern, '(') !== false) {
                                // Use domainPattern for replacement on host+path
                                $targetUrl = preg_replace('#' . $domainPattern . '#', $redirect['target'], $hostWithPath);
                            } else {
                                // Plain string match: just use target as-is
                                $targetUrl = $redirect['target'];
                            }
                        }

                        return $this->createRedirectResponse($targetUrl, $statusCode);
                    }
                }
            }
        }

        return $handler->handle($request);
    }

    protected function createRedirectResponse(string $targetUrl, int $statusCode): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        return $response->withHeader('Location', $targetUrl);
    }
}