import { createSimulationController, prepareCanvas } from './simulation-controller';

const root = document.querySelector('[data-ball-beam-simulation]');

if (root) {
    createSimulationController({
        rootSelector: '[data-ball-beam-simulation]',
        initialData: {
            time: [0],
            ball_position: [0],
            beam_angle: [0],
        },
        series: [
            {
                key: 'ball_position',
                label: root.dataset.ballPositionLabel,
                color: 'rgb(220, 38, 38)',
                axis: 'y',
            },
            {
                key: 'beam_angle',
                label: root.dataset.beamAngleLabel,
                color: 'rgb(5, 150, 105)',
                axis: 'y1',
            },
        ],
        drawFrame: drawBallBeamFrame,
    });
}

function drawBallBeamFrame(canvas, data, index) {
    const { context, width, height } = prepareCanvas(canvas);
    const ballPosition = data.ball_position[index] ?? 0;
    const beamAngle = data.beam_angle[index] ?? 0;
    const positionRange = Math.max(0.25, ...data.ball_position.map((value) => Math.abs(value)));
    const beamLength = width * 0.72;
    const pivotX = width / 2;
    const pivotY = height * 0.58;
    const directionX = Math.cos(beamAngle);
    const directionY = Math.sin(beamAngle);
    const normalX = Math.sin(beamAngle);
    const normalY = -Math.cos(beamAngle);
    const startX = pivotX - directionX * beamLength / 2;
    const startY = pivotY - directionY * beamLength / 2;
    const endX = pivotX + directionX * beamLength / 2;
    const endY = pivotY + directionY * beamLength / 2;
    const ballRadius = Math.max(12, Math.min(width, height) * 0.045);
    const normalizedPosition = clamp(ballPosition / (positionRange * 1.2), -1, 1);
    const alongBeam = normalizedPosition * beamLength * 0.42;
    const ballX = pivotX + directionX * alongBeam + normalX * (ballRadius + 6);
    const ballY = pivotY + directionY * alongBeam + normalY * (ballRadius + 6);

    context.clearRect(0, 0, width, height);
    context.fillStyle = '#f8fafc';
    context.fillRect(0, 0, width, height);

    context.fillStyle = '#e4e4e7';
    context.beginPath();
    context.moveTo(pivotX, pivotY + 12);
    context.lineTo(pivotX - 42, height * 0.88);
    context.lineTo(pivotX + 42, height * 0.88);
    context.closePath();
    context.fill();

    context.strokeStyle = '#0f172a';
    context.lineWidth = Math.max(10, height * 0.04);
    context.lineCap = 'round';
    context.beginPath();
    context.moveTo(startX, startY);
    context.lineTo(endX, endY);
    context.stroke();

    context.strokeStyle = '#94a3b8';
    context.lineWidth = 2;
    context.beginPath();
    context.moveTo(startX, startY + 18);
    context.lineTo(endX, endY + 18);
    context.stroke();

    context.fillStyle = '#ecfdf5';
    context.strokeStyle = '#047857';
    context.lineWidth = 2;
    context.beginPath();
    context.arc(pivotX, pivotY, 11, 0, Math.PI * 2);
    context.fill();
    context.stroke();

    const gradient = context.createRadialGradient(
        ballX - ballRadius * 0.35,
        ballY - ballRadius * 0.35,
        ballRadius * 0.2,
        ballX,
        ballY,
        ballRadius,
    );
    gradient.addColorStop(0, '#fecaca');
    gradient.addColorStop(1, '#dc2626');

    context.fillStyle = gradient;
    context.beginPath();
    context.arc(ballX, ballY, ballRadius, 0, Math.PI * 2);
    context.fill();

    context.strokeStyle = '#991b1b';
    context.lineWidth = 2;
    context.stroke();
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}
