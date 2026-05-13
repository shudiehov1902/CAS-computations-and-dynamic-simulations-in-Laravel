import { StreamLanguage } from '@codemirror/language';
import { octave } from '@codemirror/legacy-modes/mode/octave';
import { EditorView, basicSetup } from 'codemirror';

const consolePanel = document.querySelector('[data-cas-console]');

if (consolePanel) {
    const form = consolePanel.querySelector('.cas-console-form');
    const textarea = consolePanel.querySelector('#cas-command');
    const editorHost = consolePanel.querySelector('[data-editor-host]');
    const output = consolePanel.querySelector('[data-output]');
    const status = consolePanel.querySelector('[data-status]');
    const button = consolePanel.querySelector('[data-run-button]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const editor = new EditorView({
        doc: textarea.value,
        extensions: [
            basicSetup,
            StreamLanguage.define(octave),
            EditorView.lineWrapping,
            EditorView.theme({
                '&': {
                    minHeight: '14rem',
                    borderRadius: '0.5rem',
                    border: '1px solid rgb(212 212 216)',
                    backgroundColor: 'white',
                    fontSize: '0.95rem',
                },
                '.cm-scroller': {
                    fontFamily: '"Cascadia Mono", "SFMono-Regular", Consolas, monospace',
                },
                '&.cm-focused': {
                    outline: '2px solid rgb(209 250 229)',
                    borderColor: 'rgb(5 150 105)',
                },
            }),
        ],
        parent: editorHost,
    });

    textarea.hidden = true;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const command = editor.state.doc.toString().trim();
        textarea.value = command;

        if (command === '') {
            output.textContent = consolePanel.dataset.emptyText;
            status.textContent = '';
            return;
        }

        setBusy(true);

        try {
            const response = await fetch(consolePanel.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ command }),
            });

            const payload = await parseJson(response);

            if (response.ok && payload.success) {
                output.textContent = payload.output || consolePanel.dataset.outputPlaceholder;
                status.textContent = '';
                return;
            }

            output.textContent = [payload.output, payload.error || payload.message]
                .filter(Boolean)
                .join('\n') || response.statusText;
            status.textContent = payload.error || payload.message || response.statusText;
        } catch (error) {
            output.textContent = consolePanel.dataset.networkError;
            status.textContent = consolePanel.dataset.networkError;
        } finally {
            setBusy(false);
        }
    });

    function setBusy(isBusy) {
        button.disabled = isBusy;
        button.classList.toggle('is-loading', isBusy);
        status.textContent = isBusy ? consolePanel.dataset.loadingText : status.textContent;
    }

    async function parseJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return {
                success: false,
                output: '',
                error: response.statusText,
            };
        }
    }
}
