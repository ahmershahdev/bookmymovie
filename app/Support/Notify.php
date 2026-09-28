<?php

namespace App\Support;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * One call, two channels: an in-app notification (always) and a web push
 * to every browser the member allowed (when VAPID keys are configured).
 * Dead subscriptions (410/404 from the push service) are removed.
 */
class Notify
{
    public static function pushEnabled(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    /**
     * @param  list<int>  $userIds
     */
    public static function users(array $userIds, string $type, string $title, string $message, string $url, ?string $relatedType = null, ?int $relatedId = null): int
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return 0;
        }

        $now = now();
        foreach (array_chunk($userIds, 500) as $chunk) {
            Notification::query()->insert(array_map(fn (int $id) => [
                'user_id' => $id, 'type' => $type, 'title' => $title, 'message' => $message,
                'related_type' => $relatedType, 'related_id' => $relatedId, 'is_read' => false, 'created_at' => $now,
            ], $chunk));
        }

        if (self::pushEnabled()) {
            self::push($userIds, ['title' => $title, 'body' => $message, 'url' => $url, 'tag' => $type.':'.($relatedId ?? 0)]);
        }

        return count($userIds);
    }

    /**
     * @param  list<int>  $userIds
     * @param  array{title: string, body: string, url: string, tag?: string}  $payload
     */
    public static function push(array $userIds, array $payload): void
    {
        self::openSslConfig();

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ]], ['TTL' => 3600, 'urgency' => 'normal'], 10);
            $webPush->setReuseVAPIDHeaders(true);

            $subscriptions = DB::table('push_subscriptions')->whereIn('user_id', $userIds)->get();
            foreach ($subscriptions as $row) {
                $webPush->queueNotification(
                    Subscription::create(['endpoint' => $row->endpoint, 'keys' => ['p256dh' => $row->p256dh, 'auth' => $row->auth]]),
                    json_encode([...$payload, 'icon' => '/images/favicon/android-chrome-192x192.png'], JSON_UNESCAPED_SLASHES)
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    DB::table('push_subscriptions')->where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                }
            }
        } catch (Throwable $exception) {
            // Push is best effort; the in-app notification is already saved.
            Log::warning('Web push failed', ['error' => $exception->getMessage()]);
        }
    }

    /** XAMPP on Windows ships OpenSSL without telling PHP where its config is. */
    private static function openSslConfig(): void
    {
        if (PHP_OS_FAMILY === 'Windows' && ! getenv('OPENSSL_CONF')) {
            foreach ([dirname(PHP_BINARY).'/extras/ssl/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf'] as $path) {
                if (is_file($path)) {
                    putenv('OPENSSL_CONF='.$path);
                    break;
                }
            }
        }
    }
}
