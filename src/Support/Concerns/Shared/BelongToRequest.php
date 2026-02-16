<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

trait BelongToRequest
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function request($request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request ?? request();
    }

    /**
     * Get the request data.
     */
    public function getRequestData(): array
    {
        return $this->getRequest()->all();
    }

    /**
     * Get a specific value from the request.
     *
     * @return mixed
     */
    public function getRequestValue(string $key, mixed $default = null): mixed
    {
        return $this->getRequest()->input($key, $default);
    }

    /**
     * Check if the request has a specific key.
     */
    public function hasRequestKey(string $key): bool
    {
        return $this->getRequest()->has($key);
    }

    /**
     * Get the request instance.
     *
     * @return \Illuminate\Http\Request|null
     */
    public function getRequestInstance()
    {
        return $this->request;
    }
}
