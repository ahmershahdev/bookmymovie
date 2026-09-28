<?php

namespace App\Support;

use App\Mail\TransactionalEmail;
use App\Models\Booking;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TransactionalMailer
{
    public static function userLogin(User $user, ?Request $request = null, string $method = 'Email and password'): void
    {
        $request ??= request();

        self::send($user->email, new TransactionalEmail(
            'New sign-in to your BookMyMovie account',
            'New sign-in',
            'Your account was just signed in to. If this was you, no action is needed. If not, reset your password right away: every other device is signed out when you do.',
            [
                'Account' => $user->email,
                'Method' => $method,
                'Device' => Str::limit((string) $request->userAgent(), 80),
                'IP address' => (string) $request->ip(),
                'Time' => now()->format('j M Y, g:i A'),
            ],
            'Reset password',
            route('password.request')
        ));
    }

    public static function passwordChanged(User $user, ?Request $request = null): void
    {
        $request ??= request();

        self::send($user->email, new TransactionalEmail(
            'Your BookMyMovie password was changed',
            'Password changed',
            'The password on your account was just changed and other devices were signed out. If you did not do this, contact support immediately.',
            [
                'Account' => $user->email,
                'IP address' => (string) $request->ip(),
                'Time' => now()->format('j M Y, g:i A'),
            ],
            'Contact support',
            route('contact')
        ));
    }

    public static function paymentReceived(User $user, Booking $booking): void
    {
        self::send($booking->customer_email ?: $user->email, new TransactionalEmail(
            'Payment received '.$booking->booking_number,
            'You are all paid up',
            'Thanks, your payment went through. Show your e-ticket at the door; there is nothing to pay at the counter.',
            [
                'Booking' => $booking->booking_number,
                'Paid' => 'PKR '.number_format((float) $booking->total_amount),
                'Method' => PaymentGateway::METHODS[$booking->payment_method]['label'] ?? 'Online',
                'Time' => now()->format('j M Y, g:i A'),
            ],
            'View e-ticket',
            route('user.booking.show', $booking->booking_number)
        ));
    }

    public static function userSignup(User $user): void
    {
        self::send($user->email, new TransactionalEmail(
            'Welcome to BookMyMovie',
            'Your account is ready',
            'Thanks for joining BookMyMovie. You can now save movies, reserve seats, and manage bookings from your account.',
            ['Account' => $user->email],
            'Open dashboard',
            route('user.dashboard')
        ));
    }

    public static function emailVerificationCode(User $user, string $code): void
    {
        self::send($user->email, new TransactionalEmail(
            'Verify your BookMyMovie account',
            'Your verification code',
            'Enter this 8-character code to verify your email address. The code expires in 15 minutes.',
            [
                'Code' => $code,
                'Expires' => now()->addMinutes(15)->format('M d, Y h:i A'),
            ],
            'Verify email',
            route('user.verify.notice')
        ), now: true);
    }

    public static function passwordResetCode(string $email, string $code, string $url, bool $admin = false): void
    {
        self::send($email, new TransactionalEmail(
            ($admin ? 'Admin' : 'BookMyMovie').' password reset',
            'Reset your password',
            $admin
                ? 'Someone asked to reset the credentials for this admin account. The link works once and expires in 15 minutes. If it was not you, ignore this email.'
                : 'Someone asked to reset the password for this account. Enter this code on the reset page. It works once and expires in 15 minutes. If it was not you, ignore this email: your password has not changed.',
            $admin
                ? ['Expires' => now()->addMinutes(15)->format('j M Y, g:i A')]
                : ['Code' => $code, 'Expires' => now()->addMinutes(15)->format('j M Y, g:i A')],
            $admin ? 'Reset credentials' : 'Enter your code',
            $url
        ), now: true);
    }

    public static function bookingConfirmed(User $user, Booking $booking): void
    {
        self::send($user->email, new TransactionalEmail(
            'Booking confirmed '.$booking->booking_number,
            'Your booking is confirmed',
            'Your seats have been reserved. Keep this booking number with you when you visit the cinema.',
            [
                'Booking' => $booking->booking_number,
                'Seats' => (string) $booking->seat_count,
                'Total' => 'PKR '.number_format((float) $booking->total_amount),
                'Payment' => $booking->payment_method === 'cod' ? 'Pay at the cinema counter' : (PaymentGateway::METHODS[$booking->payment_method]['label'] ?? 'Online').($booking->payment_status === 'paid' ? ', paid' : ', awaiting payment'),
            ],
            'View booking',
            route('user.booking.show', $booking->booking_number)
        ));
    }

    public static function bookingCancelled(User $user, Booking $booking): void
    {
        self::send($user->email, new TransactionalEmail(
            'Booking cancelled '.$booking->booking_number,
            'Your booking has been cancelled',
            'Your seats have been released and nothing is owed. If you used a coupon, it is available on your account again.',
            [
                'Booking' => $booking->booking_number,
                'Seats released' => (string) $booking->seat_count,
                'Cancelled at' => now()->format('j M Y, g:i A'),
            ],
            'Browse showtimes',
            route('movies.index')
        ));
    }

    public static function bookingRefunded(User $user, Booking $booking, string $reason): void
    {
        $refunded = $booking->payment_status === 'refunded';

        self::send($booking->customer_email ?: $user->email, new TransactionalEmail(
            ($refunded ? 'Refund issued ' : 'Booking cancelled ').$booking->booking_number,
            $refunded ? 'Your refund is on its way' : 'Your booking was cancelled',
            $refunded
                ? 'The cinema cancelled this booking and refunded it. Card and wallet refunds usually reach you in 5 to 10 working days; gift card balance and loyalty points are back on your account already.'
                : 'The cinema cancelled this booking. Nothing had been paid, so nothing is owed. Any gift card balance or points you used are back on your account.',
            [
                'Booking' => $booking->booking_number,
                'Amount' => 'PKR '.number_format((float) $booking->total_amount),
                'Reason' => $reason,
            ],
            'Browse showtimes',
            route('movies.index')
        ));
    }

    public static function giftCardIssued(\App\Models\GiftCard $card): void
    {
        self::send((string) $card->recipient_email, new TransactionalEmail(
            'You have a BookMyMovie gift card',
            'A night at the movies, on us',
            ($card->message ? '“'.$card->message.'” ' : '').'Enter this code at checkout and the balance comes off your booking. Whatever is left stays on the card for next time.',
            [
                'Code' => $card->code,
                'Balance' => 'PKR '.number_format((float) $card->balance),
                'Valid until' => $card->expires_at?->format('j M Y') ?? 'No expiry',
            ],
            'Find a film',
            route('movies.index')
        ));
    }

    public static function twoFactorCode(User $user, string $code, int $minutes): void
    {
        self::send($user->email, new TransactionalEmail(
            $code.' is your BookMyMovie sign-in code',
            'Your sign-in code',
            'Enter this code to finish signing in. It expires in '.$minutes.' minutes. If you did not just try to sign in, change your password: someone knows it.',
            [
                'Code' => $code,
                'Expires' => now()->addMinutes($minutes)->format('j M Y, g:i A'),
            ],
        ), now: true);
    }

    /**
     * Receipts and notices go through the queue so the page never waits on
     * the mail provider. One-time codes are sent at once: the person is
     * standing at the form waiting for them.
     */
    private static function send(string $email, TransactionalEmail $mail, bool $now = false): void
    {
        try {
            $now ? Mail::to($email)->send($mail) : Mail::to($email)->queue($mail);
        } catch (\Throwable $exception) {
            Log::warning('Transactional email failed.', [
                'email' => $email,
                'subject' => $mail->subjectLine,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
