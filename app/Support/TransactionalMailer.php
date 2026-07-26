<?php

namespace App\Support;

use App\Mail\TransactionalEmail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionalMailer
{
    public static function userLogin(User $user): void
    {
        self::send($user->email, new TransactionalEmail(
            'New login to your BookMyMovie account',
            'New account login',
            'Your BookMyMovie account was just accessed. If this was you, no action is needed.',
            [
                'Account' => $user->email,
                'Time' => now()->format('M d, Y h:i A'),
            ]
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
            route('user.verify.notice', ['email' => $user->email])
        ));
    }

    public static function passwordResetCode(string $email, string $code, string $url, bool $admin = false): void
    {
        self::send($email, new TransactionalEmail(
            ($admin ? 'Admin' : 'Account') . ' password reset code',
            'Password reset request',
            'Use this reset link or code within 15 minutes. Ignore this email if you did not request it.',
            [
                'Code' => $code,
                'Expires' => now()->addMinutes(15)->format('M d, Y h:i A'),
            ],
            'Reset password',
            $url
        ));
    }

    public static function bookingConfirmed(User $user, Booking $booking): void
    {
        self::send($user->email, new TransactionalEmail(
            'Booking confirmed ' . $booking->booking_number,
            'Your booking is confirmed',
            'Your seats have been reserved. Keep this booking number with you when you visit the cinema.',
            [
                'Booking' => $booking->booking_number,
                'Seats' => (string) $booking->seat_count,
                'Total' => 'PKR ' . number_format((float) $booking->total_amount),
                'Payment' => strtoupper($booking->payment_method),
            ],
            'View booking',
            route('user.booking.show', $booking->booking_number)
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
