import type { ScreenshotStep } from '@verbb/craft-screenshots/types';

/** Place the real front-end gate in a compact 2x capture frame. */
export function createKnockKnockGateFrameStep(): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                document.getElementById('knock-knock-screenshot-frame')?.remove();

                const subject = document.querySelector('main') ?? document.querySelector('form.login-form');
                if (!(subject instanceof HTMLElement)) {
                    throw new Error('Knock Knock login form was not found.');
                }

                const frame = document.createElement('div');
                frame.id = 'knock-knock-screenshot-frame';
                frame.style.cssText = [
                    'position:fixed',
                    'left:0',
                    'top:0',
                    'width:512px',
                    'height:360px',
                    'overflow:hidden',
                    'background:#e9f0f7',
                    'z-index:2147483646',
                ].join(';');

                const stage = document.createElement('div');
                stage.style.cssText = [
                    'display:flex',
                    'align-items:center',
                    'justify-content:center',
                    'width:512px',
                    'height:360px',
                    'background:#e9f0f7',
                ].join(';');

                subject.style.cssText = [
                    'display:flex',
                    'align-items:center',
                    'justify-content:center',
                    'width:100%',
                    'height:100%',
                    'min-height:0',
                    'margin:0',
                ].join(';');
                stage.appendChild(subject);
                frame.appendChild(stage);
                document.body.appendChild(frame);

                document.documentElement.style.background = '#e9f0f7';
                document.body.style.margin = '0';
                document.body.style.overflow = 'hidden';
                window.scrollTo(0, 0);
            })();
        `,
    };
}
