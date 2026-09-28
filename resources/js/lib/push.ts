import axios from 'axios';
import { route } from '@/lib/utils';

export type PushState = 'unsupported' | 'denied' | 'off' | 'on';

const toBytes = (base64: string) => {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (char) => char.charCodeAt(0));
};

export function pushSupported(): boolean {
    return typeof window !== 'undefined' && window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

export async function pushState(): Promise<PushState> {
    if (!pushSupported()) return 'unsupported';
    if (Notification.permission === 'denied') return 'denied';
    const registration = await navigator.serviceWorker.getRegistration('/');
    const subscription = await registration?.pushManager.getSubscription();
    return subscription ? 'on' : 'off';
}

/** Asks permission (only ever from a click), subscribes and tells the server. */
export async function enablePush(publicKey: string): Promise<PushState> {
    if (!pushSupported()) return 'unsupported';
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return permission === 'denied' ? 'denied' : 'off';

    const registration = (await navigator.serviceWorker.getRegistration('/')) ?? (await navigator.serviceWorker.register('/sw.js', { scope: '/' }));
    await navigator.serviceWorker.ready;
    const subscription = (await registration.pushManager.getSubscription())
        ?? (await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toBytes(publicKey) }));
    await axios.post(route('push.subscribe'), subscription.toJSON());
    return 'on';
}

export async function disablePush(): Promise<PushState> {
    const registration = await navigator.serviceWorker.getRegistration('/');
    const subscription = await registration?.pushManager.getSubscription();
    if (subscription) {
        await axios.delete(route('push.unsubscribe'), { data: { endpoint: subscription.endpoint } }).catch(() => undefined);
        await subscription.unsubscribe();
    }
    return 'off';
}
