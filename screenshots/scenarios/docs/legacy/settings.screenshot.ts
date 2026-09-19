import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

export default defineScreenshotScenario({
    id: 'knock-knock-docs-legacy-settings',
    output: 'docs/legacy/settings.png',
    route: '/admin/knock-knock/settings',
    viewport: { width: 1180, height: 900, deviceScaleFactor: 2 },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'General Settings' },
        { type: 'text', text: 'Password' },
        { type: 'text', text: 'Custom Login Path' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                document.activeElement?.blur();
                const main = document.querySelector('#main');
                if (main instanceof HTMLElement) main.style.maxWidth = '840px';
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 150 } },
    ],
    target: { type: 'selector', selector: '#main', padding: 20 },
    caption: 'Knock Knock’s current general protection settings in Craft 5.',
    intent: 'Retains the legacy settings-page subject for future documentation without changing the Features page.',
});
