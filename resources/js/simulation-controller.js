import Chart from 'chart.js/auto';

export function createSimulationController(config) {
    const root = document.querySelector(config.rootSelector);

    if (!root) {
        return;
    }

    const form = root.querySelector('[data-simulation-form]');
    const canvas = root.querySelector('[data-animation-canvas]');
    const chartCanvas = root.querySelector('[data-chart-canvas]');
    const status = root.querySelector('[data-simulation-status]');
    const errorPanel = root.querySelector('[data-simulation-error]');
    const runButton = root.querySelector('[data-run-button]');
    const playButton = root.querySelector('[data-play-button]');
    const pauseButton = root.querySelector('[data-pause-button]');
    const resetButton = root.querySelector('[data-reset-button]');
    const speedControl = root.querySelector('[data-speed-control]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    let chart = null;
    let simulationData = null;
    let frameIndex = 0;
    let animationId = null;
    let playStartedAt = 0;
    let playStartedTime = 0;
    let isPlaying = false;

    if (config.initialData) {
        config.drawFrame(canvas, config.initialData, 0);
    }

    form.addEventListener('submit', runSimulation);
    playButton.addEventListener('click', play);
    pauseButton.addEventListener('click', () => pause(root.dataset.pausedText));
    resetButton.addEventListener('click', reset);
    window.addEventListener('resize', () => renderFrame());

    async function runSimulation(event) {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        pause('');
        setBusy(true);
        setError('');
        status.textContent = root.dataset.loadingText;

        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(collectPayload()),
            });
            const payload = await parseJson(response);

            if (!response.ok) {
                throw new Error(payload.error || payload.message || response.statusText);
            }

            if (!hasValidShape(payload, config.series.map((series) => series.key))) {
                throw new Error(root.dataset.invalidData);
            }

            simulationData = payload;
            frameIndex = 0;
            createChart();
            renderFrame();
            status.textContent = root.dataset.readyText;
            setPlaybackEnabled(true);
        } catch (error) {
            simulationData = null;
            setPlaybackEnabled(false);
            setError(error.message || root.dataset.networkError);
            status.textContent = '';
        } finally {
            setBusy(false);
        }
    }

    function collectPayload() {
        const payload = {};

        new FormData(form).forEach((value, key) => {
            payload[key] = Number.parseFloat(String(value));
        });

        return payload;
    }

    function play() {
        if (!simulationData) {
            return;
        }

        if (frameIndex >= simulationData.time.length - 1) {
            frameIndex = 0;
            renderFrame();
        }

        isPlaying = true;
        playStartedAt = performance.now();
        playStartedTime = simulationData.time[frameIndex] ?? 0;
        status.textContent = root.dataset.playingText;
        setPlaybackEnabled(true);
        animationId = requestAnimationFrame(tick);
    }

    function tick(now) {
        if (!simulationData || !isPlaying) {
            return;
        }

        const speed = Number.parseFloat(speedControl.value) || 1;
        const elapsedSeconds = ((now - playStartedAt) / 1000) * speed;
        const targetTime = playStartedTime + elapsedSeconds;

        while (frameIndex < simulationData.time.length - 1 && simulationData.time[frameIndex] < targetTime) {
            frameIndex += 1;
        }

        renderFrame();

        if (frameIndex >= simulationData.time.length - 1) {
            pause(root.dataset.readyText);
            return;
        }

        animationId = requestAnimationFrame(tick);
    }

    function pause(message) {
        if (animationId !== null) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }

        isPlaying = false;

        if (message !== undefined && status) {
            status.textContent = message;
        }

        setPlaybackEnabled(Boolean(simulationData));
    }

    function reset() {
        if (!simulationData) {
            return;
        }

        pause(root.dataset.resetText);
        frameIndex = 0;
        renderFrame();
    }

    function renderFrame() {
        const data = simulationData ?? config.initialData;

        if (!data) {
            return;
        }

        config.drawFrame(canvas, data, frameIndex);
        updateChartMarker();
    }

    function createChart() {
        if (chart) {
            chart.destroy();
        }

        const lineDatasets = config.series.map((series) => ({
            label: series.label,
            data: simulationData.time.map((time, index) => ({
                x: time,
                y: simulationData[series.key][index],
            })),
            borderColor: series.color,
            backgroundColor: series.color,
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.2,
            yAxisID: series.axis,
        }));

        const markerDatasets = config.series.map((series) => ({
            label: series.label,
            isMarker: true,
            data: [],
            borderColor: series.color,
            backgroundColor: series.color,
            pointRadius: 5,
            pointHoverRadius: 5,
            type: 'scatter',
            yAxisID: series.axis,
        }));

        chart = new Chart(chartCanvas, {
            type: 'line',
            data: {
                datasets: [...lineDatasets, ...markerDatasets],
            },
            options: {
                animation: false,
                maintainAspectRatio: false,
                normalized: true,
                parsing: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            filter: (item, data) => !data.datasets[item.datasetIndex].isMarker,
                            boxWidth: 12,
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: root.dataset.timeLabel,
                        },
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: config.series[0].label,
                        },
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: config.series[1].label,
                        },
                    },
                },
            },
        });
    }

    function updateChartMarker() {
        if (!chart || !simulationData) {
            return;
        }

        config.series.forEach((series, index) => {
            chart.data.datasets[config.series.length + index].data = [{
                x: simulationData.time[frameIndex],
                y: simulationData[series.key][frameIndex],
            }];
        });

        chart.update('none');
    }

    function setBusy(isBusy) {
        runButton.disabled = isBusy;
    }

    function setPlaybackEnabled(hasData) {
        playButton.disabled = !hasData || isPlaying;
        pauseButton.disabled = !hasData || !isPlaying;
        resetButton.disabled = !hasData;
    }

    function setError(message) {
        errorPanel.textContent = message;
        errorPanel.hidden = message === '';
    }
}

export function prepareCanvas(canvas) {
    const bounds = canvas.getBoundingClientRect();
    const fallbackWidth = Number.parseFloat(canvas.getAttribute('width')) || 720;
    const fallbackHeight = Number.parseFloat(canvas.getAttribute('height')) || 360;
    const width = bounds.width || fallbackWidth;
    const height = bounds.height || fallbackHeight;
    const dpr = window.devicePixelRatio || 1;
    const pixelWidth = Math.max(1, Math.round(width * dpr));
    const pixelHeight = Math.max(1, Math.round(height * dpr));

    if (canvas.width !== pixelWidth || canvas.height !== pixelHeight) {
        canvas.width = pixelWidth;
        canvas.height = pixelHeight;
    }

    const context = canvas.getContext('2d');
    context.setTransform(dpr, 0, 0, dpr, 0, 0);

    return { context, width, height };
}

async function parseJson(response) {
    try {
        return await response.json();
    } catch (error) {
        return {};
    }
}

function hasValidShape(payload, keys) {
    if (!Array.isArray(payload.time) || payload.time.length === 0) {
        return false;
    }

    return keys.every((key) => (
        Array.isArray(payload[key])
        && payload[key].length === payload.time.length
        && payload[key].every((value) => Number.isFinite(value))
    ));
}
