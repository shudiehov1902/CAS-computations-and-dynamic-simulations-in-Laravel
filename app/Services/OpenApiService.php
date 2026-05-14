<?php

namespace App\Services;

class OpenApiService
{
    /**
     * @return array<string, mixed>
     */
    public function document(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'CAS Simulations API',
                'description' => 'REST API for GNU Octave CAS execution, dynamic simulations, logs, statistics, OpenAPI JSON, and dynamic PDF documentation.',
                'version' => '1.0.0',
            ],
            'servers' => [
                [
                    'url' => url('/'),
                    'description' => 'Current Laravel application',
                ],
            ],
            'tags' => [
                ['name' => 'CAS', 'description' => 'GNU Octave command execution'],
                ['name' => 'Simulations', 'description' => 'Numerical data for synchronized frontend animations'],
                ['name' => 'Logs', 'description' => 'CAS and simulation request logs'],
                ['name' => 'Statistics', 'description' => 'Anonymous animation usage statistics'],
                ['name' => 'Documentation', 'description' => 'Machine-readable and generated API documentation'],
            ],
            'paths' => $this->paths(),
            'components' => $this->components(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paths(): array
    {
        return [
            '/api/cas/execute' => [
                'post' => [
                    'tags' => ['CAS'],
                    'summary' => 'Execute a GNU Octave CAS command',
                    'description' => 'Runs a validated Octave command and preserves helper variables per anonymous user token.',
                    'security' => [['ApiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CasExecuteRequest'],
                                'examples' => [
                                    'arithmetic' => [
                                        'summary' => 'Basic arithmetic',
                                        'value' => ['command' => '1 + 1'],
                                    ],
                                    'sessionVariable' => [
                                        'summary' => 'Store a helper variable',
                                        'value' => ['command' => 'a = 1 + 1'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => $this->jsonResponse('CAS command executed', '#/components/schemas/CasExecuteResponse', [
                            'success' => true,
                            'output' => 'ans = 2',
                            'error' => null,
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '422' => $this->jsonResponse('Validation, blocked command, or Octave runtime error', '#/components/schemas/ErrorResponse', [
                            'success' => false,
                            'output' => '',
                            'error' => 'The command field is required.',
                        ]),
                        '504' => $this->jsonResponse('Octave command timed out', '#/components/schemas/ErrorResponse', [
                            'success' => false,
                            'output' => '',
                            'error' => 'Octave execution timed out.',
                        ]),
                        '500' => ['$ref' => '#/components/responses/ServerError'],
                    ],
                ],
            ],
            '/api/simulations/pendulum' => [
                'post' => [
                    'tags' => ['Simulations'],
                    'summary' => 'Run inverted pendulum simulation',
                    'description' => 'Calculates numerical response arrays in GNU Octave using the CTMS ball-and-beam model. Ball positions are meters on a 1.0 m beam, and beam angle is radians.',
                    'security' => [['ApiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => false,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/PendulumRequest'],
                                'example' => [
                                    'reference' => 0.2,
                                    'initial_position' => 0,
                                    'initial_angle' => 0,
                                    'time_step' => 0.05,
                                    'duration' => 10,
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => $this->jsonResponse('Pendulum simulation data', '#/components/schemas/PendulumResponse', [
                            'time' => [0, 0.05, 0.1],
                            'position' => [0, 0.01, 0.02],
                            'angle' => [0, -0.001, -0.002],
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '422' => ['$ref' => '#/components/responses/ValidationOrRuntimeError'],
                        '504' => ['$ref' => '#/components/responses/Timeout'],
                        '500' => ['$ref' => '#/components/responses/ServerError'],
                    ],
                ],
            ],
            '/api/simulations/ball-beam' => [
                'post' => [
                    'tags' => ['Simulations'],
                    'summary' => 'Run ball-and-beam simulation',
                    'description' => 'Calculates numerical response arrays in GNU Octave. The frontend renders the graph and Canvas animation.',
                    'security' => [['ApiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => false,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/BallBeamRequest'],
                                'example' => [
                                    'reference' => 0.25,
                                    'initial_velocity' => 0,
                                    'initial_acceleration' => 0,
                                    'time_step' => 0.01,
                                    'duration' => 5,
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => $this->jsonResponse('Ball-and-beam simulation data', '#/components/schemas/BallBeamResponse', [
                            'time' => [0, 0.01, 0.02],
                            'ball_position' => [0, 0.01, 0.02],
                            'beam_angle' => [0, 0.0001, 0.00008],
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '422' => ['$ref' => '#/components/responses/ValidationOrRuntimeError'],
                        '504' => ['$ref' => '#/components/responses/Timeout'],
                        '500' => ['$ref' => '#/components/responses/ServerError'],
                    ],
                ],
            ],
            '/api/logs' => [
                'get' => [
                    'tags' => ['Logs'],
                    'summary' => 'List CAS backend logs',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        ['$ref' => '#/components/parameters/Page'],
                        ['$ref' => '#/components/parameters/PerPage'],
                    ],
                    'responses' => [
                        '200' => $this->jsonResponse('Paginated CAS logs', '#/components/schemas/CasLogListResponse', [
                            'data' => [
                                [
                                    'id' => 1,
                                    'created_at' => '2026-05-08T12:00:00+00:00',
                                    'command' => 'cas.execute',
                                    'status' => 'success',
                                    'request_payload' => ['command' => '1 + 1'],
                                    'output' => 'ans = 2',
                                    'error_message' => null,
                                    'ip_address' => '127.0.0.1',
                                    'user_token' => '00000000-0000-0000-0000-000000000000',
                                ],
                            ],
                            'meta' => ['current_page' => 1, 'per_page' => 20, 'total' => 1, 'last_page' => 1],
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
            '/api/logs/export' => [
                'get' => [
                    'tags' => ['Logs'],
                    'summary' => 'Export CAS backend logs as CSV',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'CSV export',
                            'content' => [
                                'text/csv' => [
                                    'schema' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                    'example' => "id,created_at,command,status,user_token,ip_address,request_payload,output,error_message\n1,2026-05-08 12:00:00,cas.execute,success,token,127.0.0.1,\"{\"\"command\"\":\"\"1 + 1\"\"}\",ans = 2,\n",
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
            '/api/statistics' => [
                'get' => [
                    'tags' => ['Statistics'],
                    'summary' => 'List animation usage counts',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => $this->jsonResponse('Animation usage summary', '#/components/schemas/StatisticsSummaryResponse', [
                            'data' => [
                                ['animation_type' => 'pendulum', 'label' => 'Inverted Pendulum', 'count' => 1],
                                ['animation_type' => 'ball_beam', 'label' => 'Ball and Beam', 'count' => 1],
                            ],
                            'interval_minutes' => 10,
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
            '/api/statistics/{animation}' => [
                'get' => [
                    'tags' => ['Statistics'],
                    'summary' => 'List animation usage details',
                    'security' => [['ApiKeyAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'animation',
                            'in' => 'path',
                            'required' => true,
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['pendulum', 'ball_beam', 'ball-beam'],
                            ],
                        ],
                        ['$ref' => '#/components/parameters/Page'],
                        ['$ref' => '#/components/parameters/PerPage'],
                    ],
                    'responses' => [
                        '200' => $this->jsonResponse('Animation usage details', '#/components/schemas/StatisticsDetailsResponse', [
                            'animation_type' => 'pendulum',
                            'label' => 'Inverted Pendulum',
                            'data' => [
                                [
                                    'id' => 1,
                                    'used_at' => '2026-05-08T12:00:00+00:00',
                                    'user_token' => '00000000-0000-0000-0000-000000000000',
                                    'ip_address' => '127.0.0.1',
                                    'city' => 'Unknown',
                                    'country' => 'Unknown',
                                ],
                            ],
                            'meta' => ['current_page' => 1, 'per_page' => 20, 'total' => 1, 'last_page' => 1],
                        ]),
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '404' => $this->messageResponse('Animation statistics not found.'),
                    ],
                ],
            ],
            '/api/openapi' => [
                'get' => [
                    'tags' => ['Documentation'],
                    'summary' => 'Get protected OpenAPI JSON',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'OpenAPI JSON document',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'additionalProperties' => true,
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
            '/api/docs/pdf' => [
                'get' => [
                    'tags' => ['Documentation'],
                    'summary' => 'Download dynamic API documentation PDF',
                    'description' => 'Generates API documentation dynamically from the current OpenAPI source.',
                    'security' => [['ApiKeyAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Generated PDF documentation',
                            'content' => [
                                'application/pdf' => [
                                    'schema' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function components(): array
    {
        return [
            'securitySchemes' => [
                'ApiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-CAS-API-Key',
                    'description' => 'API key configured by CAS_API_KEY in the Laravel environment.',
                ],
            ],
            'parameters' => [
                'Page' => [
                    'name' => 'page',
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                ],
                'PerPage' => [
                    'name' => 'per_page',
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
                ],
            ],
            'responses' => [
                'Unauthorized' => [
                    'description' => 'Invalid or missing API key',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/MessageResponse'],
                            'example' => ['message' => 'Invalid or missing API key.'],
                        ],
                    ],
                ],
                'ValidationOrRuntimeError' => $this->jsonResponse('Validation or Octave runtime error', '#/components/schemas/ErrorResponse', [
                    'success' => false,
                    'output' => '',
                    'error' => 'Invalid simulation parameters.',
                ]),
                'Timeout' => $this->jsonResponse('Octave execution timed out', '#/components/schemas/ErrorResponse', [
                    'success' => false,
                    'output' => '',
                    'error' => 'Octave execution timed out.',
                ]),
                'ServerError' => $this->jsonResponse('Unexpected server error', '#/components/schemas/ErrorResponse', [
                    'success' => false,
                    'output' => '',
                    'error' => 'Unexpected CAS execution error.',
                ]),
            ],
            'schemas' => $this->schemas(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schemas(): array
    {
        return [
            'CasExecuteRequest' => [
                'type' => 'object',
                'required' => ['command'],
                'properties' => [
                    'command' => [
                        'type' => 'string',
                        'maxLength' => 5000,
                        'example' => '1 + 1',
                    ],
                ],
            ],
            'CasExecuteResponse' => [
                'type' => 'object',
                'required' => ['success', 'output', 'error'],
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'output' => ['type' => 'string'],
                    'error' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'ErrorResponse' => [
                'type' => 'object',
                'required' => ['success', 'output', 'error'],
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => false],
                    'output' => ['type' => 'string', 'example' => ''],
                    'error' => ['type' => 'string'],
                ],
            ],
            'MessageResponse' => [
                'type' => 'object',
                'required' => ['message'],
                'properties' => [
                    'message' => ['type' => 'string'],
                ],
            ],
            'PendulumRequest' => [
                'type' => 'object',
                'properties' => [
                    'reference' => ['type' => 'number', 'minimum' => -5, 'maximum' => 5, 'default' => 0.2],
                    'initial_position' => ['type' => 'number', 'minimum' => -5, 'maximum' => 5, 'default' => 0],
                    'initial_angle' => ['type' => 'number', 'minimum' => -1.57, 'maximum' => 1.57, 'default' => 0],
                    'time_step' => ['type' => 'number', 'minimum' => 0.001, 'maximum' => 1, 'default' => 0.05],
                    'duration' => ['type' => 'number', 'minimum' => 0.1, 'maximum' => 60, 'default' => 10],
                ],
            ],
            'PendulumResponse' => [
                'type' => 'object',
                'required' => ['time', 'position', 'angle'],
                'properties' => [
                    'time' => ['$ref' => '#/components/schemas/NumberArray'],
                    'position' => ['$ref' => '#/components/schemas/NumberArray'],
                    'angle' => ['$ref' => '#/components/schemas/NumberArray'],
                ],
            ],
            'BallBeamRequest' => [
                'type' => 'object',
                'properties' => [
                    'reference' => [
                        'type' => 'number',
                        'minimum' => -0.5,
                        'maximum' => 0.5,
                        'default' => 0.25,
                        'description' => 'Target ball position in meters on the 1.0 m CTMS beam.',
                    ],
                    'initial_velocity' => [
                        'type' => 'number',
                        'minimum' => -0.5,
                        'maximum' => 0.5,
                        'default' => 0,
                        'description' => 'Backward-compatible field name used as initial ball position in meters.',
                    ],
                    'initial_acceleration' => [
                        'type' => 'number',
                        'minimum' => -0.35,
                        'maximum' => 0.35,
                        'default' => 0,
                        'description' => 'Backward-compatible field name used as initial beam angle in radians.',
                    ],
                    'time_step' => ['type' => 'number', 'minimum' => 0.001, 'maximum' => 1, 'default' => 0.01],
                    'duration' => ['type' => 'number', 'minimum' => 0.1, 'maximum' => 60, 'default' => 5],
                ],
            ],
            'BallBeamResponse' => [
                'type' => 'object',
                'required' => ['time', 'ball_position', 'beam_angle'],
                'properties' => [
                    'time' => ['$ref' => '#/components/schemas/NumberArray'],
                    'ball_position' => ['$ref' => '#/components/schemas/NumberArray'],
                    'beam_angle' => ['$ref' => '#/components/schemas/NumberArray'],
                ],
            ],
            'NumberArray' => [
                'type' => 'array',
                'items' => ['type' => 'number'],
            ],
            'CasLog' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'command' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['success', 'error']],
                    'request_payload' => ['type' => 'object', 'nullable' => true, 'additionalProperties' => true],
                    'output' => ['type' => 'string', 'nullable' => true],
                    'error_message' => ['type' => 'string', 'nullable' => true],
                    'ip_address' => ['type' => 'string', 'nullable' => true],
                    'user_token' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'CasLogListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/CasLog']],
                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                ],
            ],
            'StatisticsSummaryResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/StatisticsSummaryItem'],
                    ],
                    'interval_minutes' => ['type' => 'integer'],
                ],
            ],
            'StatisticsSummaryItem' => [
                'type' => 'object',
                'properties' => [
                    'animation_type' => ['type' => 'string', 'enum' => ['pendulum', 'ball_beam']],
                    'label' => ['type' => 'string'],
                    'count' => ['type' => 'integer'],
                ],
            ],
            'StatisticsDetailsResponse' => [
                'type' => 'object',
                'properties' => [
                    'animation_type' => ['type' => 'string', 'enum' => ['pendulum', 'ball_beam']],
                    'label' => ['type' => 'string'],
                    'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/AnimationUsage'],
                    ],
                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                ],
            ],
            'AnimationUsage' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'used_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'user_token' => ['type' => 'string'],
                    'ip_address' => ['type' => 'string', 'nullable' => true],
                    'city' => ['type' => 'string'],
                    'country' => ['type' => 'string'],
                ],
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $example
     * @return array<string, mixed>
     */
    private function jsonResponse(string $description, string $schemaRef, array $example): array
    {
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => $schemaRef],
                    'example' => $example,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messageResponse(string $message): array
    {
        return [
            'description' => $message,
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/MessageResponse'],
                    'example' => ['message' => $message],
                ],
            ],
        ];
    }
}
