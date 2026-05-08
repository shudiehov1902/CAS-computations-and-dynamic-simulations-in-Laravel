<?php

namespace App\Services;

use Illuminate\Support\Arr;

class DocumentationPdfService
{
    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function buildViewData(array $document): array
    {
        return [
            'info' => $document['info'] ?? [],
            'securitySchemes' => Arr::get($document, 'components.securitySchemes', []),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'groups' => $this->groupEndpoints($document),
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<int, array{name: string, description: string, endpoints: array<int, array<string, mixed>>}>
     */
    public function groupEndpoints(array $document): array
    {
        $groups = [];

        foreach ($document['tags'] ?? [] as $tag) {
            $name = (string) ($tag['name'] ?? 'Other');

            $groups[$name] = [
                'name' => $name,
                'description' => (string) ($tag['description'] ?? ''),
                'endpoints' => [],
            ];
        }

        foreach ($document['paths'] ?? [] as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }

            foreach ($operations as $method => $operation) {
                if (! is_array($operation) || ! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                $tagName = (string) (($operation['tags'][0] ?? null) ?: 'Other');
                $groups[$tagName] ??= [
                    'name' => $tagName,
                    'description' => '',
                    'endpoints' => [],
                ];

                $groups[$tagName]['endpoints'][] = [
                    'method' => strtoupper((string) $method),
                    'path' => (string) $path,
                    'summary' => (string) ($operation['summary'] ?? ''),
                    'description' => (string) ($operation['description'] ?? ''),
                    'requiresAuth' => ! empty($operation['security']),
                    'requestExamples' => $this->requestExamples($operation),
                    'responseExamples' => $this->responseExamples($operation),
                ];
            }
        }

        return array_values(array_filter(
            $groups,
            fn (array $group): bool => count($group['endpoints']) > 0
        ));
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, array{name: string, summary: string, body: string}>
     */
    private function requestExamples(array $operation): array
    {
        $jsonContent = Arr::get($operation, 'requestBody.content.application/json', []);

        if (! is_array($jsonContent)) {
            return [];
        }

        if (array_key_exists('examples', $jsonContent) && is_array($jsonContent['examples'])) {
            return collect($jsonContent['examples'])
                ->map(fn (array $example, string $name): array => [
                    'name' => $name,
                    'summary' => (string) ($example['summary'] ?? ''),
                    'body' => $this->formatExample($example['value'] ?? null),
                ])
                ->values()
                ->all();
        }

        if (array_key_exists('example', $jsonContent)) {
            return [[
                'name' => 'example',
                'summary' => '',
                'body' => $this->formatExample($jsonContent['example']),
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<int, array{status: string, contentType: string, body: string}>
     */
    private function responseExamples(array $operation): array
    {
        $examples = [];

        foreach ($operation['responses'] ?? [] as $status => $response) {
            if (! is_array($response)) {
                continue;
            }

            foreach ($response['content'] ?? [] as $contentType => $content) {
                if (! is_array($content) || ! array_key_exists('example', $content)) {
                    continue;
                }

                $examples[] = [
                    'status' => (string) $status,
                    'contentType' => (string) $contentType,
                    'body' => $this->formatExample($content['example']),
                ];
            }
        }

        return $examples;
    }

    private function formatExample(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
