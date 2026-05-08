<?php

namespace App\Services;

use JsonException;

class PendulumService
{
    private const JSON_START = '__WEBTE2_PENDULUM_JSON_START__';

    private const JSON_END = '__WEBTE2_PENDULUM_JSON_END__';

    public function __construct(
        private readonly OctaveService $octaveService,
    ) {
    }

    /**
     * @param  array{reference: float, initial_position: float, initial_angle: float, time_step: float, duration: float}  $parameters
     * @return array{time: array<int, float>, position: array<int, float>, angle: array<int, float>}
     */
    public function simulate(array $parameters): array
    {
        $script = $this->buildScript($parameters);
        $result = $this->octaveService->executeScript($script);

        return $this->parseOutput($result['output']);
    }

    /**
     * @return array{time: array<int, float>, position: array<int, float>, angle: array<int, float>}
     */
    public function parseOutput(string $output): array
    {
        $pattern = '/'.preg_quote(self::JSON_START, '/').'\s*(.*?)\s*'.preg_quote(self::JSON_END, '/').'/s';

        if (preg_match($pattern, $output, $matches) !== 1) {
            throw new OctaveExecutionException('Pendulum simulation output did not contain JSON data.', 422, $output);
        }

        try {
            $decoded = json_decode(trim($matches[1]), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new OctaveExecutionException(
                'Pendulum simulation returned invalid JSON: '.$exception->getMessage(),
                422,
                $output
            );
        }

        if (! is_array($decoded)) {
            throw new OctaveExecutionException('Pendulum simulation JSON must be an object.', 422, $output);
        }

        $time = $this->numericArray($decoded, 'time', $output);
        $position = $this->numericArray($decoded, 'position', $output);
        $angle = $this->numericArray($decoded, 'angle', $output);

        if (count($time) !== count($position) || count($time) !== count($angle)) {
            throw new OctaveExecutionException('Pendulum simulation arrays must have the same length.', 422, $output);
        }

        return [
            'time' => $time,
            'position' => $position,
            'angle' => $angle,
        ];
    }

    /**
     * @param  array{reference: float, initial_position: float, initial_angle: float, time_step: float, duration: float}  $parameters
     */
    private function buildScript(array $parameters): string
    {
        return sprintf(
            <<<'OCTAVE'
more off;
page_screen_output(0);
page_output_immediately(1);

function __webte2_print_vector__(__webte2_values__)
  printf('[');

  for __webte2_i__ = 1:numel(__webte2_values__)
    if __webte2_i__ > 1
      printf(',');
    endif

    printf('%%.12g', __webte2_values__(__webte2_i__));
  endfor

  printf(']');
endfunction

try
  pkg load control;

  M = .5;
  m = 0.2;
  b = 0.1;
  I = 0.006;
  g = 9.8;
  l = 0.3;
  p = I*(M+m)+M*m*l^2;
  A = [0 1 0 0; 0 -(I+m*l^2)*b/p (m^2*g*l^2)/p 0; 0 0 0 1; 0 -(m*l*b)/p m*g*l*(M+m)/p 0];
  B = [0; (I+m*l^2)/p; 0; m*l/p];
  C = [1 0 0 0; 0 0 1 0];
  D = [0; 0];
  K = lqr(A,B,C'*C,1);
  Ac = [(A-B*K)];
  N = -inv(C(1,:)*inv(A-B*K)*B);
  sys = ss(Ac,B*N,C,D);

  t = 0:%s:%s;
  r = %s;
  initPozicia = %s;
  initUhol = %s;

  [y,t,x] = lsim(sys,r*ones(size(t)),t,[initPozicia;0;initUhol;0]);

  __webte2_time__ = t(:)';
  __webte2_position__ = y(:,1)';
  __webte2_angle__ = y(:,2)';

  printf('%s\n');
  printf('{"time":');
  __webte2_print_vector__(__webte2_time__);
  printf(',"position":');
  __webte2_print_vector__(__webte2_position__);
  printf(',"angle":');
  __webte2_print_vector__(__webte2_angle__);
  printf("}\n");
  printf('%s\n');
catch __webte2_error__
  fprintf(2, 'Pendulum simulation error: %%s\n', __webte2_error__.message);
  exit(1);
end_try_catch
OCTAVE,
            $this->number($parameters['time_step']),
            $this->number($parameters['duration']),
            $this->number($parameters['reference']),
            $this->number($parameters['initial_position']),
            $this->number($parameters['initial_angle']),
            self::JSON_START,
            self::JSON_END,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, float>
     */
    private function numericArray(array $data, string $key, string $output): array
    {
        if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
            throw new OctaveExecutionException("Pendulum simulation JSON is missing {$key} array.", 422, $output);
        }

        $values = [];

        foreach ($data[$key] as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new OctaveExecutionException("Pendulum simulation {$key} array contains a non-numeric value.", 422, $output);
            }

            $values[] = (float) $value;
        }

        return $values;
    }

    private function number(float|int $value): string
    {
        $formatted = rtrim(rtrim(sprintf('%.12F', (float) $value), '0'), '.');

        return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
    }
}
