import { createSimulationController, prepareCanvas } from './simulation-controller';

const root = document.querySelector('[data-pendulum-simulation]');

if (root) {
    createSimulationController({
        rootSelector: '[data-pendulum-simulation]',
        initialData: {
            time: [0],
            position: [0],
            angle: [0],
        },
        series: [
            {
                key: 'position',
                label: root.dataset.positionLabel,
                color: 'rgb(5, 150, 105)',
                axis: 'y',
            },
            {
                key: 'angle',
                label: root.dataset.angleLabel,
                color: 'rgb(37, 99, 235)',
                axis: 'y1',
            },
        ],
        drawFrame: drawPendulumFrame,
    });
}

function drawPendulumFrame(canvas, data, index) {
    const { context, width, height } = prepareCanvas(canvas);
    const position = data.position[index] ?? 0;
    const angle = data.angle[index] ?? 0;
    const range = 5;
    const trackY = height * 0.74;
    const centerX = width / 2 + clamp(position / (range * 1.25), -1, 1) * width * 0.36;
    const cartWidth = Math.min(90, width * 0.16);
    const cartHeight = Math.min(42, height * 0.13);
    const cartY = trackY - cartHeight - 10;
    const pivotX = centerX;
    const pivotY = cartY + 5;
    const rodLength = Math.min(width, height) * 0.32;
    const bobRadius = Math.max(10, Math.min(width, height) * 0.035);
    const bobX = pivotX + Math.sin(angle) * rodLength;
    const bobY = pivotY - Math.cos(angle) * rodLength;

    context.clearRect(0, 0, width, height);
    context.fillStyle = '#f8fafc';
    context.fillRect(0, 0, width, height);

    context.strokeStyle = '#d4d4d8';
    context.lineWidth = 2;
    context.beginPath();
    context.moveTo(width * 0.08, trackY);
    context.lineTo(width * 0.92, trackY);
    context.stroke();

    context.strokeStyle = '#a1a1aa';
    context.lineWidth = 1;
    for (let x = width * 0.1; x <= width * 0.9; x += width * 0.08) {
        context.beginPath();
        context.moveTo(x, trackY + 6);
        context.lineTo(x - 12, trackY + 20);
        context.stroke();
    }

    context.fillStyle = '#0f172a';
    roundRect(context, centerX - cartWidth / 2, cartY, cartWidth, cartHeight, 8);
    context.fill();

    context.fillStyle = '#64748b';
    context.beginPath();
    context.arc(centerX - cartWidth * 0.27, trackY - 6, 8, 0, Math.PI * 2);
    context.arc(centerX + cartWidth * 0.27, trackY - 6, 8, 0, Math.PI * 2);
    context.fill();

    context.strokeStyle = '#059669';
    context.lineWidth = 5;
    context.lineCap = 'round';
    context.beginPath();
    context.moveTo(pivotX, pivotY);
    context.lineTo(bobX, bobY);
    context.stroke();

    context.fillStyle = '#2563eb';
    context.beginPath();
    context.arc(bobX, bobY, bobRadius, 0, Math.PI * 2);
    context.fill();

    context.fillStyle = '#ecfdf5';
    context.strokeStyle = '#047857';
    context.lineWidth = 2;
    context.beginPath();
    context.arc(pivotX, pivotY, 7, 0, Math.PI * 2);
    context.fill();
    context.stroke();
}

function roundRect(context, x, y, width, height, radius) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.lineTo(x + width - radius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + radius);
    context.lineTo(x + width, y + height - radius);
    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    context.lineTo(x + radius, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - radius);
    context.lineTo(x, y + radius);
    context.quadraticCurveTo(x, y, x + radius, y);
    context.closePath();
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}
