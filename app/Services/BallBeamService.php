<?php

namespace App\Services;

use JsonException;

class BallBeamService
{
    private const JSON_START = '__WEBTE2_BALL_BEAM_JSON_START__';

    private const JSON_END = '__WEBTE2_BALL_BEAM_JSON_END__';

    public function __construct(
        private readonly OctaveService $octaveService,
    ) {
    }

    /**
     * @param  array{reference: float, initial_velocity: float, initial_acceleration: float, time_step: float, duration: float}  $parameters
     * @return array{time: array<int, float>, ball_position: array<int, float>, beam_angle: array<int, float>}
     */
    public function simulate(array $parameters): array
    {
        $script = $this->buildScript($parameters);
        $result = $this->octaveService->executeScript($script);

        return $this->parseOutput($result['output']);
    }

    /**
     * @return array{time: array<int, float>, ball_position: array<int, float>, beam_angle: array<int, float>}
     */
    public function parseOutput(string $output): array
    {
        $pattern = '/'.preg_quote(self::JSON_START, '/').'\s*(.*?)\s*'.preg_quote(self::JSON_END, '/').'/s';

        if (preg_match($pattern, $output, $matches) !== 1) {
            throw new OctaveExecutionException('Ball and beam simulation output did not contain JSON data.', 422, $output);
        }

        try {
            $decoded = json_decode(trim($matches[1]), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new OctaveExecutionException(
                'Ball and beam simulation returned invalid JSON: '.$exception->getMessage(),
                422,
                $output
            );
        }

        if (! is_array($decoded)) {
            throw new OctaveExecutionException('Ball and beam simulation JSON must be an object.', 422, $output);
        }

        $time = $this->numericArray($decoded, 'time', $output);
        $ballPosition = $this->numericArray($decoded, 'ball_position', $output);
        $beamAngle = $this->numericArray($decoded, 'beam_angle', $output);

        if (count($time) !== count($ballPosition) || count($time) !== count($beamAngle)) {
            throw new OctaveExecutionException('Ball and beam simulation arrays must have the same length.', 422, $output);
        }

        return [
            'time' => $time,
            'ball_position' => $ballPosition,
            'beam_angle' => $beamAngle,
        ];
    }

    /**
     * @param  array{reference: float, initial_velocity: float, initial_acceleration: float, time_step: float, duration: float}  $parameters
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

  m = 0.111;
  R = 0.015;
  g = -9.8;
  J = 9.99e-6;
  H = -m*g/(J/(R^2)+m);
  A = [0 1 0 0; 0 0 H 0; 0 0 0 1; 0 0 0 0];
  B = [0;0;0;1];
  C = [1 0 0 0];
  D = [0];
  K = place(A,B,[-2+2i,-2-2i,-20,-80]);
  N = -inv(C*inv(A-B*K)*B);
  sys = ss(A-B*K,B,C,D);

  t = 0:%s:%s;
  r = %s;
  initRychlost = %s;
  initZrychlenie = %s;

  [y,t,x] = lsim(N*sys,r*ones(size(t)),t,[initRychlost;0;initZrychlenie;0]);

  __webte2_time__ = t(:)';
  __webte2_ball_position__ = y(:)';
  __webte2_beam_angle__ = x(:,3)';

  printf('%s\n');
  printf('{"time":');
  __webte2_print_vector__(__webte2_time__);
  printf(',"ball_position":');
  __webte2_print_vector__(__webte2_ball_position__);
  printf(',"beam_angle":');
  __webte2_print_vector__(__webte2_beam_angle__);
  printf("}\n");
  printf('%s\n');
catch __webte2_error__
  fprintf(2, 'Ball and beam simulation error: %%s\n', __webte2_error__.message);
  exit(1);
end_try_catch
OCTAVE,
            $this->number($parameters['time_step']),
            $this->number($parameters['duration']),
            $this->number($parameters['reference']),
            $this->number($parameters['initial_velocity']),
            $this->number($parameters['initial_acceleration']),
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
            throw new OctaveExecutionException("Ball and beam simulation JSON is missing {$key} array.", 422, $output);
        }

        $values = [];

        foreach ($data[$key] as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new OctaveExecutionException("Ball and beam simulation {$key} array contains a non-numeric value.", 422, $output);
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
