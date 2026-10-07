<?php
final class Mailer {
    public static function welcome(string $to, string $name, string $username, string $link): void {
        $body = "Hi $name,\n\nYour eskayvie account has been created.\nUsername: $username\n"
              . "Password: the one you chose at signup\n\nConfirm your account:\n$link\n";
        @mail($to, 'Welcome to eskayvie - confirm your account', $body,
              'From: ' . cfg('mail_from') . "\r\nContent-Type: text/plain; charset=UTF-8");
    }
}
