import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { createKnockKnockGateFrameStep } from '../../support/presets';

export default defineScreenshotScenario({
    id: 'knock-knock-feature-tour-overview',
    output: 'feature-tour/knock-knock-gate-craft5.png',
    route: '/knock-knock/who-is-there',
    viewport: {
        width: 560,
        height: 420,
        deviceScaleFactor: 2,
    },
    expectedOutput: {
        width: 1024,
        height: 720,
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: 'form.login-form', state: 'visible' },
        { type: 'text', text: 'Site locked' },
    ],
    preSteps: [
        createKnockKnockGateFrameStep(),
        { type: 'wait', waitFor: { type: 'selector', selector: '#knock-knock-screenshot-frame', state: 'visible' } },
    ],
    target: {
        type: 'selector',
        selector: '#knock-knock-screenshot-frame',
        padding: 0,
    },
    caption: 'Knock Knock’s shared-password gate using the current Craft 5 login presentation.',
    intent: 'Shows the current Craft 5 password gate in a compact centred frame without the oversized legacy staging canvas.',
});
