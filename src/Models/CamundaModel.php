<?php

declare(strict_types=1);

namespace Laravolt\Camunda\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

abstract class CamundaModel extends DataTransferObject
{
    public $id;

    public function fetch()
    {
        $attributes = $this->get('');

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    protected function post(string $url, array $data = [], bool $json = true)
    {
        return $this->request($url, 'post', $this->getData($data, $json));
    }

    protected function put(string $url, array $data = [], bool $json = true)
    {
        return $this->request($url, 'put', $this->getData($data, $json));
    }

    public function delete(string $url, array $data = [], bool $json = true)
    {
        return $this->request($url, 'delete', $this->getData($data, $json));
    }

    protected function getData(array $data, bool $json): array
    {
        if (Arr::has($data, 'multipart')) {
            return $data;
        } elseif ($json) {
            return ['json' => $data];
        } else {
            return array_merge(['json' => ['a' => 'b']], $data);
        }
    }

    protected function get(string $url)
    {
        return $this->request($url, 'get');
    }

    protected function request($url, $method, $data = [])
    {
        $data['auth'] = [
            Config::get('laravolt.camunda.api.auth.user'), Config::get('laravolt.camunda.api.auth.password'),
        ];

        $url = $this->buildUrl($url);

        $response = $this->client->{$method}($url, $data);

        return json_decode((string) $response->getBody());
    }

    protected function buildUrl(string $url): string
    {
        $modelUri = (empty($this->id) && empty($this->key)) || Str::contains($url, '?') ? '' : $this->modelUri().'/';
        $baseUrl = parse_url(Config::get('laravolt.camunda.api.url', ''));
        $path = trim($baseUrl['path'] ?? '', '/');

        return $path.'/'.$modelUri.$url;
    }

    protected function modelUri(): string
    {
        if ($this->id) {
            return Str::kebab(class_basename($this)).'/'.$this->id;
        } else {
            return Str::kebab(class_basename($this)).'/key/'.$this->key.$this->tenant();
        }
    }

    protected function tenant(): ?string
    {
        return strlen($this->tenantId ?? '') ? '/tenant-id/'.$this->tenantId : null;
    }

    protected function formatVariables(array $data): array
    {
        return $data;
    }
}
