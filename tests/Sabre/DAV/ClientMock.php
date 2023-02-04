<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ClientMock extends Client
{
    public $request;
    public $response;

    public $url;
    public array $curlSettings;

    /**
     * Just making this method public.
     *
     * @param string $url
     *
     * @return string
     */
    public function getAbsoluteUrl($url)
    {
        return parent::getAbsoluteUrl($url);
    }

    public function doRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return $this->response;
    }
}
