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
        ));
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
        ));
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

    private static function send(string $email, TransactionalEmail $mail): void
    {
        try {
            Mail::to($email)->send($mail);
        } catch (\Throwable $exception) {
            Log::warning('Transactional email failed.', [
                'email' => $email,
                'subject' => $mail->subjectLine,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
