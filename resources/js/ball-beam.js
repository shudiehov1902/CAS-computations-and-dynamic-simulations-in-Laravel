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
                color: 'rgb(248, 113, 113)',
                axis: 'y',
            },
            {
                key: 'beam_angle',
                label: root.dataset.beamAngleLabel,
                color: 'rgb(52, 211, 153)',
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
    const positionRange = 5;
    const beamLength = width * 0.72;
    const pivotX = width / 2;
    const pivotY = height * 0.58;
    const directionX = Math.cos(beamAngle);
    const directionY = Math.sin(beamAngle);
    const normalX = Math.sin(beamAngle);
    const normalY = -Math.cos(beamAngle);
    const beamThickness = Math.max(10, height * 0.04);
    const startX = pivotX - directionX * beamLength / 2;
    const startY = pivotY - directionY * beamLength / 2;
    const endX = pivotX + directionX * beamLength / 2;
    const endY = pivotY + directionY * beamLength / 2;
    const ballRadius = clamp(Math.min(width, height) * 0.038, 11, 22);
    const normalizedPosition = clamp(ballPosition / positionRange, -1, 1);
    const alongBeam = normalizedPosition * beamLength * 0.46;
    const contactOffset = beamThickness / 2 + ballRadius - 1;
    const ballX = pivotX + directionX * alongBeam + normalX * contactOffset;
    const ballY = pivotY + directionY * alongBeam + normalY * contactOffset;

    context.clearRect(0, 0, width, height);
    context.fillStyle = '#06100f';
    context.fillRect(0, 0, width, height);

    context.strokeStyle = 'rgba(45, 212, 191, 0.09)';
    context.lineWidth = 1;
    for (let x = width * 0.08; x <= width * 0.92; x += width * 0.08) {
        context.beginPath();
        context.moveTo(x, height * 0.12);
        context.lineTo(x, height * 0.92);
        context.stroke();
    }

    context.fillStyle = 'rgba(212, 212, 216, 0.16)';
    context.beginPath();
    context.moveTo(pivotX, pivotY + 12);
    context.lineTo(pivotX - 42, height * 0.88);
    context.lineTo(pivotX + 42, height * 0.88);
    context.closePath();
    context.fill();

    context.strokeStyle = '#e5e7eb';
    context.lineWidth = beamThickness;
    context.lineCap = 'round';
    context.beginPath();
    context.moveTo(startX, startY);
    context.lineTo(endX, endY);
    context.stroke();

    context.strokeStyle = 'rgba(34, 211, 238, 0.42)';
    context.lineWidth = 2;
    context.beginPath();
    context.moveTo(startX, startY + 18);
    context.lineTo(endX, endY + 18);
    context.stroke();

    context.fillStyle = '#052e2b';
    context.strokeStyle = '#5eead4';
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
    gradient.addColorStop(0, '#fee2e2');
    gradient.addColorStop(0.55, '#ef4444');
    gradient.addColorStop(1, '#7f1d1d');

    context.fillStyle = gradient;
    context.beginPath();
    context.arc(ballX, ballY, ballRadius, 0, Math.PI * 2);
    context.fill();

    context.strokeStyle = '#fecaca';
    context.lineWidth = 2;
    context.stroke();
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}
